<?php

namespace glook\apist;

use glook\apist\Selectors\ApistSelector;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class ApistMethod
{
    /**
     * @var Apist
     */
    protected $resource;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * @var array|ApistSelector|null
     */
    protected $schemaBlueprint;

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var string|null
     */
    protected $content;

    protected Crawler $crawler;

    /**
     * @var ResponseInterface|null
     */
    protected $response;

    /**
     * @param Apist $resource
     * @param string|null $url
     * @param array|ApistSelector|null $schemaBlueprint
     */
    public function __construct($resource, $url, $schemaBlueprint)
    {
        $this->resource = $resource;
        $this->url = $url;
        $this->schemaBlueprint = $schemaBlueprint;
        $this->crawler = new Crawler();
    }

    /**
     * Perform method action
     *
     * @param array $arguments
     * @return array|string
     */
    public function get(array $arguments = [])
    {
        try {
            $this->makeRequest($arguments);
        } catch (ConnectException $e) {
            $url = (string) $e->getRequest()->getUri();

            return $this->errorResponse($e->getCode(), $e->getMessage(), $url);
        } catch (RequestException $e) {
            $url = (string) $e->getRequest()->getUri();
            $status = $e->getCode();
            $response = $e->getResponse();
            $reason = $e->getMessage();

            if (!is_null($response)) {
                $reason = $response->getReasonPhrase();
            }

            return $this->errorResponse($status, $reason, $url);
        }

        return $this->parseBlueprint($this->schemaBlueprint);
    }

    /**
     * Make http request
     *
     * @param array $arguments
     * @throws GuzzleException
     */
    protected function makeRequest(array $arguments = [])
    {
        $defaults = $this->getDefaultOptions();
        $arguments = array_merge($defaults, $arguments);
        $client = $this->resource->getGuzzle();

        $response = $client->request($this->getMethod(), $this->url, $arguments);
        $this->setResponse($response);
        $this->setContent((string) $response->getBody());
    }

    /**
     * @param array|ApistSelector|null $blueprint
     * @param DomCrawler|null $node
     * @return mixed
     */
    public function parseBlueprint($blueprint, ?DomCrawler $node = null)
    {
        if (is_null($blueprint)) {
            return $this->content;
        }

        if (!is_array($blueprint)) {
            $blueprint = $this->parseBlueprintValue($blueprint, $node);
        } else {
            array_walk_recursive($blueprint, function (&$value) use ($node): void {
                $value = $this->parseBlueprintValue($value, $node);
            });
        }

        return $blueprint;
    }

    /**
     * @param mixed $value
     * @param DomCrawler|null $node
     * @return mixed
     */
    protected function parseBlueprintValue($value, ?DomCrawler $node)
    {
        if ($value instanceof ApistSelector) {
            return $value->getValue($this, $node);
        }

        return $value;
    }

    /**
     * Response with error
     *
     * @param int $status
     * @param string $reason
     * @param string $url
     * @return array
     */
    protected function errorResponse(int $status, string $reason, string $url): array
    {
        return [
            'url' => $url,
            'error' => [
                'status' => $status,
                'reason' => $reason,
            ],
        ];
    }

    /**
     * @return Crawler
     */
    public function getCrawler(): Crawler
    {
        return $this->crawler;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        $this->crawler->addContent($content);

        return $this;
    }

    /**
     * @return array
     */
    protected function getDefaultOptions(): array
    {
        return [
            'cookies' => new CookieJar(),
        ];
    }

    /**
     * @return Apist
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
