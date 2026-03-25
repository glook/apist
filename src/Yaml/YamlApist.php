<?php

namespace glook\apist\Yaml;

use glook\apist\Apist;
use InvalidArgumentException;

class YamlApist extends Apist
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @param array $options
     * @param null|mixed $file
     */
    public function __construct($file = null, $options = [])
    {
        if (!is_null($file)) {
            $this->loadFromYml($file);
        }
        parent::__construct($options);
    }

    /**
     * Load method data from yaml file
     * @param $file
     */
    protected function loadFromYml($file)
    {
        $this->parser = new Parser($file);
        $this->parser->load($this);
    }

    /**
     * @param $name
     * @param $arguments
     * @return array
     */
    public function __call($name, $arguments)
    {
        if (is_null($this->parser)) {
            throw new InvalidArgumentException("Method '$name' not found.'");
        }
        $method = $this->parser->getMethod($name);
        $method = $this->parser->insertMethodArguments($method, $arguments);
        $httpMethod = isset($method['method']) ? strtoupper($method['method']) : 'GET';
        $options = $method['options'] ?? [];

        return $this->request($httpMethod, $method['url'], $method['blueprint'], $options);
    }
}
