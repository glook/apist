<?php namespace glook\apist;


use glook\apist\Selectors\ApistSelector;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class ApistMethod
{
    /**
     * @var Apist
     */
    protected $resource;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var ApistSelector[]|ApistSelector
     */
    protected $schemaBlueprint;
    /**
     * @var string
     */
    protected $method = 'GET';
    /**
     * @var string
     */
    protected $content;
    /**
     * @var Crawler
     */
    protected $crawler;
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param $resource
     * @param $url
     * @param $schemaBlueprint
     */
    function __construct($resource, $url, $schemaBlueprint)
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
     * @return array
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
     */
    protected function makeRequest(array $arguments = [])
    {
        $defaults = $this->getDefaultOptions();
        $arguments = array_merge($defaults, $arguments);
        $client = $this->resource->getGuzzle();

        $response = $client->request($this->getMethod(), $this->url, $arguments);
        $this->setResponse($response);
        $this->setContent((string)$response->getBody());
    }

    /**
     * @param $blueprint
     * @param null $node
     * @return array|string
     */
    public function parseBlueprint($blueprint, $node = null)
    {
        if (is_null($blueprint)) {
            return $this->content;
        }
        if (!is_array($blueprint)) {
            $blueprint = $this->parseBlueprintValue($blueprint, $node);
        } else {
            array_walk_recursive($blueprint, function (&$value) use ($node) {
                $value = $this->parseBlueprintValue($value, $node);
            });
        }
        return $blueprint;
    }

    /**
     * @param $value
     * @param $node
     * @return array|string
     */
    protected function parseBlueprintValue($value, $node)
    {
        if ($value instanceof ApistSelector) {
            return $value->getValue($this, $node);
        }
        return $value;
    }

    /**
     * Response with error
     *
     * @param $status
     * @param $reason
     * @param $url
     * @return array
     */
    protected function errorResponse(int $status, string $reason, string $url): array
    {
        return [
            'url' => $url,
            'error' => [
                'status' => $status,
                'reason' => $reason,
            ]
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
    public function setContent($content)
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
            'cookies' => new CookieJar
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
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
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
