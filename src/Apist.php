<?php namespace glook\apist;

use glook\apist\Selectors\ApistFilter;
use glook\apist\Selectors\ApistSelector;
use glook\apist\Yaml\YamlApist;
use GuzzleHttp\Client;

abstract class Apist
{
    /**
     * @var string
     */
    protected $baseUri;
    /**
     * @var Client
     */
    protected $guzzle;
    /**
     * @var ApistMethod
     */
    protected $currentMethod;
    /**
     * @var ApistMethod
     */
    protected $lastMethod;
    /**
     * @var bool
     */
    protected $suppressExceptions = true;

    /**
     * @param array $options
     */
    function __construct($options = [])
    {
        $options['base_uri'] = $this->getBaseUri();
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
    public function setGuzzle($guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Create filter object
     *
     * @param $cssSelector
     * @return ApistFilter
     */
    public static function filter($cssSelector)
    {
        return new ApistSelector($cssSelector);
    }

    /**
     * Get current node
     *
     * @return ApistFilter
     */
    public static function current()
    {
        return static::filter('*');
    }

    /**
     * Initialize api from yaml configuration file
     *
     * @param $file
     * @return YamlApist
     */
    public static function fromYaml($file): YamlApist
    {
        return new YamlApist($file, []);
    }

    /**
     * @return ApistMethod
     */
    public function getCurrentMethod(): ApistMethod
    {
        return $this->currentMethod;
    }

    /**
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * fallback method for backward compatibility
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->getBaseUri();
    }

    /**
     * @param string $baseUri
     */
    public function setBaseUri(string $baseUri)
    {
        $this->baseUri = $baseUri;
    }

    /**
     * fallback method for backward compatibility
     * @param string $baseUrl
     * @return void
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->setBaseUri($baseUrl);
    }

    /**
     * @return ApistMethod
     */
    public function getLastMethod()
    {
        return $this->lastMethod;
    }

    /**
     * @return boolean
     */
    public function isSuppressExceptions(): bool
    {
        return $this->suppressExceptions;
    }

    /**
     * @param boolean $suppressExceptions
     */
    public function setSuppressExceptions(bool $suppressExceptions): void
    {
        $this->suppressExceptions = $suppressExceptions;
    }

    /**
     * @param $httpMethod
     * @param $url
     * @param $blueprint
     * @param array $options
     * @return array|string
     */
    protected function request($httpMethod, $url, $blueprint, array $options = [])
    {
        $this->currentMethod = new ApistMethod($this, $url, $blueprint);
        $this->lastMethod = $this->currentMethod;
        $this->currentMethod->setMethod($httpMethod);

        $result = $this->currentMethod->get($options);
        $this->currentMethod = null;

        return $result;
    }

    /**
     * @param $content
     * @param $blueprint
     * @return array|string
     */
    protected function parse($content, $blueprint)
    {
        $this->currentMethod = new ApistMethod($this, null, $blueprint);
        $this->currentMethod->setContent($content);
        $result = $this->currentMethod->parseBlueprint($blueprint);
        $this->currentMethod = null;
        return $result;
    }

    /**
     * @param $url
     * @param $blueprint
     * @param array $options
     * @return array|string
     */
    protected function get($url, $blueprint = null, array $options = [])
    {
        return $this->request('GET', $url, $blueprint, $options);
    }

    /**
     * @param $url
     * @param $blueprint
     * @param array $options
     * @return array|string
     */
    protected function head($url, $blueprint = null, array $options = [])
    {
        return $this->request('HEAD', $url, $blueprint, $options);
    }

    /**
     * @param $url
     * @param $blueprint
     * @param array $options
     * @return array|string
     */
    protected function post($url, $blueprint = null, array $options = [])
    {
        return $this->request('POST', $url, $blueprint, $options);
    }

    /**
     * @param $url
     * @param $blueprint
     * @param array $options
     * @return array|string
     */
    protected function put($url, $blueprint = null, array $options = [])
    {
        return $this->request('PUT', $url, $blueprint, $options);
    }

    /**
     * @param $url
     * @param $blueprint
     * @param array $options
     * @return array|string
     */
    protected function patch($url, $blueprint = null, array $options = [])
    {
        return $this->request('PATCH', $url, $blueprint, $options);
    }

    /**
     * @param $url
     * @param $blueprint
     * @param array $options
     * @return array|string
     */
    protected function delete($url, $blueprint = null, array $options = [])
    {
        return $this->request('DELETE', $url, $blueprint, $options);
    }

}
