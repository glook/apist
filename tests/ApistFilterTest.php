<?php

namespace glook\apist\tests;

use glook\apist\Apist;
use glook\apist\ApistMethod;
use glook\apist\Crawler;
use glook\apist\Selectors\ApistFilter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ApistFilterTest extends TestCase
{
    public const TEST_HTML = '<html><body>
<ul class="list">
  <li class="item" data-id="1">  Alpha  </li>
  <li class="item" data-id="2">Beta</li>
  <li class="item stop" data-id="3">Gamma</li>
  <li class="item" data-id="4">Delta</li>
</ul>
<div class="parent"><div class="child"><span class="inner">Hello</span></div></div>
<p class="number">42</p>
<p class="decimal">3.14</p>
<a href="http://example.com" class="link">Click</a>
</body></html>';

    protected $resource;

    protected $apistMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = new TestApi();
        $this->apistMethod = new ApistMethod($this->resource, '/', null);
        $this->apistMethod->setContent(self::TEST_HTML);
    }

    protected function makeFilter(string $selector): ApistFilter
    {
        $node = $this->apistMethod->getCrawler()->filter($selector);

        return new ApistFilter($node, $this->apistMethod);
    }

    /** @test */
    public function it_returns_text_content()
    {
        $filter = $this->makeFilter('.inner');
        $this->assertEquals('Hello', $filter->text());
    }

    /** @test */
    public function it_returns_inner_html()
    {
        $filter = $this->makeFilter('.child');
        $this->assertStringContainsString('<span', $filter->html());
    }

    /** @test */
    public function it_returns_attribute_value()
    {
        $filter = $this->makeFilter('.link');
        $this->assertEquals('http://example.com', $filter->attr('href'));
    }

    /** @test */
    public function it_checks_attribute_exists()
    {
        $filter = $this->makeFilter('.link');
        $this->assertTrue($filter->hasAttr('href'));
        $this->assertFalse($filter->hasAttr('nonexistent'));
    }

    /** @test */
    public function it_returns_first_element()
    {
        $filter = $this->makeFilter('.item');
        $first = $filter->first();
        $this->assertCount(1, $first);
        $this->assertEquals('Alpha', trim($first->text()));
    }

    /** @test */
    public function it_returns_last_element()
    {
        $filter = $this->makeFilter('.item');
        $last = $filter->last();
        $this->assertCount(1, $last);
        $this->assertEquals('Delta', $last->text());
    }

    /** @test */
    public function it_returns_element_by_position()
    {
        $filter = $this->makeFilter('.item');
        $second = $filter->eq(1);
        $this->assertEquals('Beta', $second->text());
    }

    /** @test */
    public function it_returns_children()
    {
        $filter = $this->makeFilter('.list');
        $children = $filter->children();
        $this->assertGreaterThan(0, $children->count());
    }

    /** @test */
    public function it_returns_next_sibling()
    {
        $filter = $this->makeFilter('.item:first');
        $next = $filter->next();
        $this->assertEquals('Beta', $next->text());
    }

    /** @test */
    public function it_returns_prev_sibling()
    {
        $filter = $this->makeFilter('.item:last');
        $prev = $filter->prev();
        $this->assertEquals('Gamma', $prev->text());
    }

    /** @test */
    public function it_returns_all_next_siblings()
    {
        $filter = $this->makeFilter('.item:first');
        $all = $filter->nextAll();
        $this->assertGreaterThanOrEqual(3, $all->count());
    }

    /** @test */
    public function it_returns_all_prev_siblings()
    {
        $filter = $this->makeFilter('.item:last');
        $all = $filter->prevAll();
        $this->assertGreaterThanOrEqual(3, $all->count());
    }

    /** @test */
    public function it_returns_closest_ancestor()
    {
        $filter = $this->makeFilter('.inner');
        $closest = $filter->closest('.parent');
        $this->assertGreaterThan(0, $closest->count());
    }

    /** @test */
    public function it_checks_element_exists_true()
    {
        $filter = $this->makeFilter('.item');
        $this->assertTrue($filter->exists());
    }

    /** @test */
    public function it_checks_element_exists_false()
    {
        $node = $this->apistMethod->getCrawler()->filter('.nonexistent');
        $filter = new ApistFilter($node, $this->apistMethod);
        $this->assertFalse($filter->exists());
    }

    /** @test */
    public function it_trims_text()
    {
        $filter = $this->makeFilter('.item:first');
        $result = $filter->trim();
        $this->assertEquals('Alpha', $result);
    }

    /** @test */
    public function it_ltrims_text()
    {
        // Test ltrim with a string node (bypassing DomCrawler whitespace normalization)
        $filter = new ApistFilter('  Alpha  ', $this->apistMethod);
        $result = $filter->ltrim();
        $this->assertEquals('Alpha  ', $result);
    }

    /** @test */
    public function it_rtrims_text()
    {
        // Test rtrim with a string node (bypassing DomCrawler whitespace normalization)
        $filter = new ApistFilter('  Alpha  ', $this->apistMethod);
        $result = $filter->rtrim();
        $this->assertEquals('  Alpha', $result);
    }

    /** @test */
    public function it_converts_to_int()
    {
        $filter = $this->makeFilter('.number');
        $this->assertSame(42, $filter->intval());
    }

    /** @test */
    public function it_converts_to_float()
    {
        $filter = $this->makeFilter('.decimal');
        $this->assertSame(3.14, $filter->floatval());
    }

    /** @test */
    public function it_replaces_string_in_text()
    {
        $filter = $this->makeFilter('.inner');
        $result = $filter->str_replace('Hello', 'World');
        $this->assertEquals('World', $result);
    }

    /** @test */
    public function it_calls_custom_callback()
    {
        $filter = $this->makeFilter('.inner');
        $result = $filter->call(function ($node) {
            return strtoupper($node->text());
        });
        $this->assertEquals('HELLO', $result);
    }

    /** @test */
    public function it_check_delegates_to_call()
    {
        $filter = $this->makeFilter('.inner');
        $result = $filter->check(function ($node) {
            return $node->count() > 0;
        });
        $this->assertTrue($result);
    }

    /** @test */
    public function it_then_executes_blueprint_when_node_is_true()
    {
        $filter = new ApistFilter(true, $this->apistMethod);
        $result = $filter->then('static_value');
        $this->assertEquals('static_value', $result);
    }

    /** @test */
    public function it_then_returns_node_when_node_is_not_true()
    {
        $filter = new ApistFilter(false, $this->apistMethod);
        $result = $filter->then('static_value');
        $this->assertFalse($result);
    }

    /** @test */
    public function it_iterates_each_with_null_returns_nodes()
    {
        $filter = $this->makeFilter('.item');
        $result = $filter->each(null);
        $this->assertIsArray($result);
        $this->assertCount(4, $result);
    }

    /** @test */
    public function it_iterates_each_with_callable()
    {
        $filter = $this->makeFilter('.item');
        $result = $filter->each(function ($node) {
            return trim($node->text());
        });
        $this->assertEquals(['Alpha', 'Beta', 'Gamma', 'Delta'], $result);
    }

    /** @test */
    public function it_iterates_each_with_blueprint()
    {
        $filter = $this->makeFilter('.item');
        $result = $filter->each([
            'id' => Apist::filter('*')->attr('data-id'),
        ]);
        $this->assertCount(4, $result);
        $this->assertEquals('1', $result[0]['id']);
    }

    /** @test */
    public function it_finds_elements_by_selector()
    {
        $filter = $this->makeFilter('.list');
        $found = $filter->find('.item');
        $this->assertCount(4, $found);
    }

    /** @test */
    public function it_filters_nodes_by_selector()
    {
        $filter = $this->makeFilter('.item');
        $result = $filter->filterNodes('.stop');
        $this->assertGreaterThan(0, $result->count());
    }

    /** @test */
    public function it_checks_node_matches_selector()
    {
        $filter = $this->makeFilter('.item:first');
        $this->assertFalse($filter->is('.stop'));

        $filter2 = $this->makeFilter('.stop');
        $this->assertTrue($filter2->is('.stop'));
    }

    /** @test */
    public function it_returns_raw_element()
    {
        $filter = $this->makeFilter('.inner');
        $element = $filter->element();
        $this->assertInstanceOf(Crawler::class, $element);
    }

    /** @test */
    public function it_throws_when_guard_crawler_called_with_non_crawler()
    {
        $filter = new ApistFilter('just a string', $this->apistMethod);
        $this->expectException(InvalidArgumentException::class);
        $filter->text();
    }

    /** @test */
    public function it_guard_text_auto_converts_crawler_to_text_before_trim()
    {
        $filter = $this->makeFilter('.inner');
        // Call trim() on a Crawler node — guardText() should auto-extract text first
        $result = $filter->trim();
        $this->assertEquals('Hello', $result);
    }
}
