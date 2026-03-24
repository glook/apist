<?php

namespace glook\apist\Yaml;

use glook\apist\Apist;
use glook\apist\Selectors\ApistSelector;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

class Parser
{
    /**
     * @var array
     */
    protected array $methods = [];

    /**
     * @var array
     */
    protected array $structures = [];

    /**
     * @var string
     */
    protected $file;

    /**
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @param Apist $resource
     */
    public function load(Apist $resource): void
    {
        $data = Yaml::parse($this->file);

        if (isset($data['baseUri'])) {
            $resource->setBaseUri($data['baseUri']);
            unset($data['baseUri']);
        } elseif (isset($data['baseUrl'])) {
            $resource->setBaseUri($data['baseUrl']);
            unset($data['baseUrl']);
        }
        foreach ($data as $method => $methodConfig) {
            if ($method[0] === '_') {
                // structure
                $this->structures[$method] = $methodConfig;
            } else {
                // method
                if (!isset($methodConfig['blueprint'])) {
                    $methodConfig['blueprint'] = null;
                }

                if (!is_null($methodConfig['blueprint'])) {
                    $methodConfig['blueprint'] = $this->parseBlueprint($methodConfig['blueprint']);
                }
                $this->methods[$method] = $methodConfig;
            }
        }
    }

    /**
     * @param array|string $blueprint
     * @return mixed
     */
    protected function parseBlueprint($blueprint)
    {
        $callback = function (&$value): void {
            if (is_string($value)) {
                $value = str_replace(':current', '*', $value);
            }

            if (is_string($value) && $value[0] === ':') {
                // structure
                $structure = $this->getStructure($value);
                $value = $this->parseBlueprint($structure);

                return;
            }

            if (!is_string($value) || strpos($value, '|') === false) {
                return;
            }

            $parts = preg_split('/\s?\|\s?/', $value);
            $selector = array_shift($parts);
            $value = Apist::filter($selector);
            foreach ($parts as $part) {
                $this->addCallbackToFilter($value, $part);
            }
        };

        if (!is_array($blueprint)) {
            $callback($blueprint);
        } else {
            array_walk_recursive($blueprint, $callback);
        }

        return $blueprint;
    }

    /**
     * @param ApistSelector $filter
     * @param string $callback
     */
    protected function addCallbackToFilter(ApistSelector $filter, $callback)
    {
        $method = strtok($callback, '(),');
        $arguments = [];
        while (($argument = strtok('(),')) !== false) {
            $argument = trim($argument);

            if (preg_match('/^[\'"].*[\'"]$/', $argument)) {
                $argument = substr($argument, 1, -1);
            }

            if ($argument[0] === ':') {
                // structure
                $structure = $this->getStructure($argument);
                $argument = $this->parseBlueprint($structure);
            }
            $arguments[] = $argument;
        }
        $filter->addCallback($method, $arguments);
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function getStructure(string $name)
    {
        $structure = '_' . substr($name, 1);

        if (!isset($this->structures[$structure])) {
            throw new InvalidArgumentException("Structure '$structure' not found.'");
        }

        return $this->structures[$structure];
    }

    /**
     * @param string $name
     * @return array
     */
    public function getMethod(string $name)
    {
        if (!isset($this->methods[$name])) {
            throw new InvalidArgumentException("Method '$name' not found.'");
        }
        $methodConfig = $this->methods[$name];

        return $methodConfig;
    }

    /**
     * @param array $method
     * @param array $arguments
     * @return array
     */
    public function insertMethodArguments($method, $arguments)
    {
        array_walk_recursive($method, function (&$value) use ($arguments): void {
            if (!is_string($value)) {
                return;
            }
            $value = preg_replace_callback('/\$(?<num>\d+)/', function ($finded) use ($arguments) {
                $argumentPosition = intval($finded['num']) - 1;

                return $arguments[$argumentPosition] ?? '';
            }, $value);
        });

        return $method;
    }
}
