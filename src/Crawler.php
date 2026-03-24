<?php

namespace glook\apist;

use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;

class Crawler extends SymfonyCrawler
{
    protected array $pseudoClasses = [
        'first',
        'last',
        'eq',
    ];

    /**
     * @param string $selector
     * @return Crawler
     */
    public function filter(string $selector)
    {
        if (($result = $this->parsePseudoClasses($selector)) instanceof Crawler) {
            return $result;
        }

        return parent::filter($selector);
    }

    /**
     * @param string $selector
     * @return Crawler|null
     */
    protected function parsePseudoClasses(string $selector)
    {
        foreach ($this->pseudoClasses as $pseudoClass) {
            if (preg_match('/^(?<first>.*?):' . $pseudoClass . '(\((?<param>[0-9]+)\))?(?<last>.*)$/', $selector, $attrs)) {
                $result = $this->filter($attrs['first']);
                $args = isset($attrs['param']) ? [(int) $attrs['param']] : [];
                $result = call_user_func_array([$result, $pseudoClass], $args);
                $filter = $attrs['last'];

                if (trim($filter) !== '') {
                    $result = $result->filter($filter);
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * @return void
     */
    public function remove(): void
    {
        foreach ($this as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * @return self
     */
    public function parent(): self
    {
        $ar = $this->parents();

        return new static($ar->getNode(0), $this->uri);
    }
}
