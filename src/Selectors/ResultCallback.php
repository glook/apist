<?php

namespace glook\apist\Selectors;

use glook\apist\ApistMethod;
use glook\apist\Crawler;
use InvalidArgumentException;

class ResultCallback
{
    /**
     * @var string
     */
    protected string $methodName;

    /**
     * @var array
     */
    protected array $arguments;

    /**
     * @param string $methodName
     * @param array $arguments
     */
    public function __construct(string $methodName, array $arguments)
    {
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    /**
     * Apply result callback to the $node, provided by $method
     *
     * @param array|Crawler|string|bool $node
     * @param ApistMethod $method
     * @return mixed
     */
    public function apply($node, ApistMethod $method)
    {
        if (is_array($node)) {
            return $this->applyToArray($node, $method);
        }

        $methodName = $this->methodName;

        if ($methodName === 'else') {
            if (is_bool($node)) {
                $node = !$node;
            }
            $methodName = 'then';
        }

        $filter = new ApistFilter($node, $method);

        if (method_exists($filter, $methodName)) {
            return call_user_func_array([
                    $filter,
                    $methodName,
                ], $this->arguments);
        }

        if ($this->isResourceMethod($method)) {
            return $this->callResourceMethod($method, $node);
        }

        if ($this->isNodeMethod($node)) {
            return $this->callNodeMethod($node);
        }

        if ($this->isGlobalFunction()) {
            return $this->callGlobalFunction($node);
        }

        throw new InvalidArgumentException("Method '{$methodName}' was not found");
    }

    /**
     * @param array $array
     * @param ApistMethod $method
     * @return array
     */
    protected function applyToArray(array $array, ApistMethod $method): array
    {
        $result = [];
        foreach ($array as $node) {
            $result[] = $this->apply($node, $method);
        }

        return $result;
    }

    /**
     * @param ApistMethod $method
     * @return bool
     */
    protected function isResourceMethod(ApistMethod $method): bool
    {
        return method_exists($method->getResource(), $this->methodName);
    }

    /**
     * @param ApistMethod $method
     * @param Crawler|string|bool $node
     * @return mixed
     */
    protected function callResourceMethod(ApistMethod $method, $node)
    {
        $arguments = $this->arguments;
        array_unshift($arguments, $node);

        return call_user_func_array([
            $method->getResource(),
            $this->methodName,
        ], $arguments);
    }

    /**
     * @param Crawler|string|bool $node
     * @return bool
     */
    protected function isNodeMethod($node): bool
    {
        return method_exists($node, $this->methodName);
    }

    /**
     * @param object $node
     * @return mixed
     */
    protected function callNodeMethod($node)
    {
        return call_user_func_array([
            $node,
            $this->methodName,
        ], $this->arguments);
    }

    /**
     * @return bool
     */
    protected function isGlobalFunction(): bool
    {
        return function_exists($this->methodName);
    }

    /**
     * @param string|Crawler $node
     * @return mixed
     */
    protected function callGlobalFunction($node)
    {
        if (is_object($node)) {
            $node = $node->text();
        }
        $arguments = $this->arguments;
        array_unshift($arguments, $node);

        return call_user_func_array($this->methodName, $arguments);
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
