<?php

namespace glook\apist;

use glook\apist\Selectors\ApistSelector;
use GuzzleHttp\Client;

abstract class Apist
{
    /**
     * @var string|null
     */
    protected ?string $baseUrl = null;

    protected Client $guzzle;

    /**
     * @var ApistMethod|null
     */
    protected $currentMethod;

    /**
     * @var ApistMethod|null
     */
    protected $lastMethod;

    /**
     * @var bool
     */
    protected $suppressExceptions = true;

    /**
     * List of default request options to be merged with method call options
     * @var array
     */
    protected array $requestOptions = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $options['base_uri'] = $this->getBaseUrl();
        $this->guzzle = new Client($options);
    }

    /**
     * @return Client
     */
    public function getGuzzle()
    {
        return $this->guzzle;
    }

    /**
     * @param Client $guzzle
     */
    public function setGuzzle(Client $guzzle): void
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Create filter object
     *
     * @param string $cssSelector
     * @return ApistSelector
     */
    public static function filter($cssSelector)
    {
        return new ApistSelector($cssSelector);
    }

    /**
     * Get current node
     *
     * @return ApistSelector
     */
    public static function current()
    {
        return static::filter('*');
    }

    /**
     * @return ApistMethod|null
     */
    public function getCurrentMethod(): ?ApistMethod
    {
        return $this->currentMethod;
    }

    /**
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUri
     */
    public function setBaseUrl(string $baseUri): void
    {
        $this->baseUrl = $baseUri;
    }

    /**
     * @return ApistMethod|null
     */
    public function getLastMethod(): ?ApistMethod
    {
        return $this->lastMethod;
    }

    /**
     * @return bool
     */
    public function isSuppressExceptions(): bool
    {
        return $this->suppressExceptions;
    }

    /**
     * @param bool $suppressExceptions
     */
    public function setSuppressExceptions(bool $suppressExceptions): void
    {
        $this->suppressExceptions = $suppressExceptions;
    }

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array|ApistSelector|null $blueprint
     * @param array $options
     * @return array|string
     */
    protected function request(string $httpMethod, string $url, $blueprint, array $options = [])
    {
        $this->currentMethod = new ApistMethod($this, $url, $blueprint);
        $this->lastMethod = $this->currentMethod;
        $this->currentMethod->setMethod($httpMethod);

        $result = $this->currentMethod->get($options);
        $this->currentMethod = null;

        return $result;
    }

    /**
     * @param string $content
     * @param array|ApistSelector|null $blueprint
     * @return array|string
     */
    protected function parse(string $content, $blueprint)
    {
        $this->currentMethod = new ApistMethod($this, null, $blueprint);
        $this->currentMethod->setContent($content);
        $result = $this->currentMethod->parseBlueprint($blueprint);
        $this->currentMethod = null;

        return $result;
    }

    /**
     * @param string $url
     * @param array|ApistSelector|null $blueprint
     * @param array $options
     * @return array|string
     */
    protected function get(string $url, $blueprint = null, array $options = [])
    {
        return $this->request('GET', $url, $blueprint, array_merge($this->requestOptions, $options));
    }

    /**
     * @param string $url
     * @param array|ApistSelector|null $blueprint
     * @param array $options
     * @return array|string
     */
    protected function head(string $url, $blueprint = null, array $options = [])
    {
        return $this->request('HEAD', $url, $blueprint, array_merge($this->requestOptions, $options));
    }

    /**
     * @param string $url
     * @param array|ApistSelector|null $blueprint
     * @param array $options
     * @return array|string
     */
    protected function post(string $url, $blueprint = null, array $options = [])
    {
        return $this->request('POST', $url, $blueprint, array_merge($this->requestOptions, $options));
    }

    /**
     * @param string $url
     * @param array|ApistSelector|null $blueprint
     * @param array $options
     * @return array|string
     */
    protected function put(string $url, $blueprint = null, array $options = [])
    {
        return $this->request('PUT', $url, $blueprint, array_merge($this->requestOptions, $options));
    }

    /**
     * @param string $url
     * @param array|ApistSelector|null $blueprint
     * @param array $options
     * @return array|string
     */
    protected function patch(string $url, $blueprint = null, array $options = [])
    {
        return $this->request('PATCH', $url, $blueprint, array_merge($this->requestOptions, $options));
    }

    /**
     * @param string $url
     * @param array|ApistSelector|null $blueprint
     * @param array $options
     * @return array|string
     */
    protected function delete(string $url, $blueprint = null, array $options = [])
    {
        return $this->request('DELETE', $url, $blueprint, array_merge($this->requestOptions, $options));
    }
}
