<?php

namespace glook\apist\Selectors;

use Exception;
use glook\apist\ApistMethod;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ApistSelector
 *
 * @method $this text()
 * @method $this html()
 * @method $this filter(string $selector)
 * @method $this filterNodes(string $selector)
 * @method $this find(string $selector)
 * @method $this children()
 * @method $this prev()
 * @method $this prevAll()
 * @method $this prevUntil(string $selector)
 * @method $this next()
 * @method $this nextAll()
 * @method $this nextUntil(string $selector)
 * @method $this nodeUntil(string $selector, string $direction)
 * @method $this is(string $selector)
 * @method $this closest(string $selector)
 * @method $this attr(string $attribute)
 * @method $this hasAttr(string $attribute)
 * @method $this eq(int $position)
 * @method $this first()
 * @method $this last()
 * @method $this element()
 * @method $this call(callable $callback)
 * @method $this trim(string $mask = " \t\n\r\0\x0B")
 * @method $this ltrim(string $mask = " \t\n\r\0\x0B")
 * @method $this rtrim(string $mask = " \t\n\r\0\x0B")
 * @method $this str_replace(array|string $search, array|string $replace, ?int $count = null)
 * @method $this intval()
 * @method $this floatval()
 * @method $this exists()
 * @method $this check(callable $callback)
 * @method $this then(array|ApistSelector $blueprint)
 * @method $this else(array|ApistSelector $blueprint)
 * @method $this each(null|array|callable $blueprint = null)
 */
class ApistSelector
{
    protected string $selector;

    /**
     * @var ResultCallback[]
     */
    protected array $resultMethodChain = [];

    /**
     * @param string $selector
     */
    public function __construct(string $selector)
    {
        $this->selector = $selector;
    }

    /**
     * Get value from content by css selector
     *
     * @param ApistMethod $method
     * @param Crawler|null $rootNode
     * @return mixed
     */
    public function getValue(ApistMethod $method, ?Crawler $rootNode = null)
    {
        if (is_null($rootNode)) {
            $rootNode = $method->getCrawler();
        }
        $result = $rootNode->filter($this->selector);

        return $this->applyResultCallbackChain($result, $method);
    }

    /**
     * Save callable method as result callback to perform it after getValue method
     *
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public function __call(string $name, array $arguments)
    {
        return $this->addCallback($name, $arguments);
    }

    /**
     * Apply all result callbacks
     *
     * @param Crawler $node
     * @param ApistMethod $method
     * @return mixed
     */
    protected function applyResultCallbackChain(Crawler $node, ApistMethod $method)
    {
        if ($this->resultMethodChain === []) {
            $this->addCallback('text');
        }
        /** @var ResultCallback[] $traceStack */
        $traceStack = [];
        foreach ($this->resultMethodChain as $resultCallback) {
            try {
                $traceStack[] = $resultCallback;
                $node = $resultCallback->apply($node, $method);
            } catch (InvalidArgumentException $e) {
                if ($method->getResource()->isSuppressExceptions()) {
                    return null;
                }
                $message = $this->createExceptionMessage($e, $traceStack);

                throw new InvalidArgumentException($message, 0, $e);
            }
        }

        return $node;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return $this
     */
    public function addCallback(string $name, array $arguments = []): self
    {
        $resultCallback = new ResultCallback($name, $arguments);
        $this->resultMethodChain[] = $resultCallback;

        return $this;
    }

    /**
     * @param Exception $e
     * @param ResultCallback[] $traceStack
     * @return string
     */
    protected function createExceptionMessage(Exception $e, array $traceStack): string
    {
        $message = "[ filter({$this->selector})";
        foreach ($traceStack as $callback) {
            $message .= '->' . $callback->getMethodName() . '(';

            try {
                $message .= implode(', ', $callback->getArguments());
            } catch (Exception $_e) {
            }
            $message .= ')';
        }
        $message .= ' ] ' . $e->getMessage();

        return $message;
    }
}
