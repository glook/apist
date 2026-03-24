<?php

namespace glook\apist\Selectors;

use glook\apist\Apist;
use glook\apist\ApistMethod;
use glook\apist\Crawler;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

/**
 * Class ApistFilter
 */
class ApistFilter
{
    /**
     * @var Crawler|bool|string
     */
    protected $node;

    /**
     * @var Apist
     */
    protected Apist $resource;

    protected ApistMethod $method;

    /**
     * @param Crawler|bool|string $node
     * @param ApistMethod $method
     */
    public function __construct($node, ApistMethod $method)
    {
        $this->node = $node;
        $this->method = $method;
        $this->resource = $method->getResource();
    }

    /**
     * @return string
     */
    public function text(): string
    {
        $this->guardCrawler();

        return $this->node->text();
    }

    /**
     * @return string
     */
    public function html(): string
    {
        $this->guardCrawler();

        return $this->node->html();
    }

    /**
     * @param string $selector
     * @return Crawler
     */
    public function filter(string $selector): Crawler
    {
        $this->guardCrawler();

        return $this->node->filter($selector);
    }

    /**
     * @param string $selector
     * @return Crawler
     */
    public function filterNodes(string $selector): Crawler
    {
        $this->guardCrawler();
        $rootNode = $this->method->getCrawler();
        $crawler = new Crawler();
        $rootNode->filter($selector)->each(function (Crawler $filteredNode) use ($crawler): void {
            $filteredNode = $filteredNode->getNode(0);
            foreach ($this->node as $node) {
                if ($filteredNode === $node) {
                    $crawler->add($node);

                    break;
                }
            }
        });

        return $crawler;
    }

    /**
     * @param string $selector
     * @return Crawler
     */
    public function find(string $selector): Crawler
    {
        $this->guardCrawler();

        return $this->node->filter($selector);
    }

    /**
     * @return Crawler
     */
    public function children(): Crawler
    {
        $this->guardCrawler();

        return $this->node->children();
    }

    /**
     * @return Crawler
     */
    public function prev(): Crawler
    {
        $this->guardCrawler();

        return $this->prevAll()->first();
    }

    /**
     * @return Crawler
     */
    public function prevAll(): Crawler
    {
        $this->guardCrawler();

        return $this->node->previousAll();
    }

    /**
     * @param string $selector
     * @return Crawler
     */
    public function prevUntil($selector): Crawler
    {
        return $this->nodeUntil($selector, 'prev');
    }

    /**
     * @return Crawler
     */
    public function next(): Crawler
    {
        $this->guardCrawler();

        return $this->nextAll()->first();
    }

    /**
     * @return Crawler
     */
    public function nextAll(): Crawler
    {
        $this->guardCrawler();

        return $this->node->nextAll();
    }

    /**
     * @param string $selector
     * @return Crawler
     */
    public function nextUntil($selector): Crawler
    {
        return $this->nodeUntil($selector, 'next');
    }

    /**
     * @param string $selector
     * @param string $direction
     * @return Crawler
     */
    public function nodeUntil($selector, $direction): Crawler
    {
        $this->guardCrawler();
        $crawler = new Crawler();
        $filter = new static($this->node, $this->method);
        while (1) {
            $node = $filter->$direction();

            if ($node->count() === 0) {
                break;
            }
            $filter->node = $node;

            if ($filter->is($selector)) {
                break;
            }
            $crawler->add($node->getNode(0));
        }

        return $crawler;
    }

    /**
     * @param mixed $selector
     * @return bool
     */
    public function is(string $selector): bool
    {
        $this->guardCrawler();

        return count($this->filterNodes($selector)) > 0;
    }

    /**
     * @param string $selector
     * @return Crawler
     */
    public function closest(string $selector): Crawler
    {
        $this->guardCrawler();
        $this->node = $this->node->parents();

        return $this->filterNodes($selector)->last();
    }

    /**
     * @param string $attribute
     * @return null|string
     */
    public function attr(string $attribute): ?string
    {
        $this->guardCrawler();

        return $this->node->attr($attribute);
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function hasAttr(string $attribute): bool
    {
        $this->guardCrawler();

        return !is_null($this->node->attr($attribute));
    }

    /**
     * @param int $position
     * @return Crawler
     */
    public function eq(int $position): Crawler
    {
        $this->guardCrawler();

        return $this->node->eq($position);
    }

    /**
     * @return Crawler
     */
    public function first(): Crawler
    {
        $this->guardCrawler();

        return $this->node->first();
    }

    /**
     * @return Crawler
     */
    public function last(): Crawler
    {
        $this->guardCrawler();

        return $this->node->last();
    }

    /**
     * @return Crawler|bool|string
     */
    public function element()
    {
        return $this->node;
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    public function call(callable $callback)
    {
        return $callback($this->node);
    }

    /**
     * @param string $mask
     * @return string
     */
    public function trim(string $mask = " \t\n\r\0\x0B"): string
    {
        $this->guardText();

        return trim($this->node, $mask);
    }

    /**
     * @param string $mask
     * @return string
     */
    public function ltrim(string $mask = " \t\n\r\0\x0B"): string
    {
        $this->guardText();

        return ltrim($this->node, $mask);
    }

    /**
     * @param string $mask
     * @return string
     */
    public function rtrim(string $mask = " \t\n\r\0\x0B"): string
    {
        $this->guardText();

        return rtrim($this->node, $mask);
    }

    /**
     * @param array|string $search
     * @param array|string $replace
     * @param null|int $count
     * @return string
     */
    public function str_replace($search, $replace, $count = null): string
    {
        $this->guardText();

        return str_replace($search, $replace, $this->node, $count);
    }

    /**
     * @return int
     */
    public function intval(): int
    {
        $this->guardText();

        return intval($this->node);
    }

    /**
     * @return float
     */
    public function floatval(): float
    {
        $this->guardText();

        return floatval($this->node);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        if ($this->node instanceof Crawler) {
            return $this->node->count() > 0;
        }

        return (bool) $this->node;
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    public function check(callable $callback)
    {
        return $this->call($callback);
    }

    /**
     * @param array|ApistSelector $blueprint
     * @return mixed
     */
    public function then($blueprint)
    {
        if ($this->node === true) {
            return $this->method->parseBlueprint($blueprint);
        }

        return $this->node;
    }

    /**
     * @param null|array|callable $blueprint
     * @return array
     */
    public function each($blueprint = null): array
    {
        $callback = $blueprint;

        if (is_null($callback)) {
            $callback = (fn ($node) => $node);
        }

        if (!is_callable($callback)) {
            $callback = fn (DomCrawler $node) => $this->method->parseBlueprint($blueprint, $node);
        }

        return $this->node->each($callback);
    }

    /**
     * Guard string method to be called with Crawler object
     */
    protected function guardText()
    {
        if (is_object($this->node)) {
            $this->node = $this->node->text();
        }
    }

    /**
     * Guard method to be called with Crawler object
     */
    protected function guardCrawler()
    {
        if (!$this->node instanceof Crawler) {
            throw new InvalidArgumentException('Current node isnt instance of Crawler.');
        }
    }
}
