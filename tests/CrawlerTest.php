<?php

namespace glook\apist\tests;

use glook\apist\Crawler;
use PHPUnit\Framework\TestCase;

class CrawlerTest extends TestCase
{
    public const TEST_HTML = '<html><body>
<ul class="list">
  <li class="item" data-id="1">Alpha</li>
  <li class="item" data-id="2">Beta</li>
  <li class="item stop" data-id="3">Gamma</li>
  <li class="item" data-id="4">Delta</li>
</ul>
<div class="parent"><div class="child"><span class="inner">Hello</span></div></div>
<p class="number">42</p>
</body></html>';

    protected function makeCrawler(): Crawler
    {
        $crawler = new Crawler();
        $crawler->addContent(self::TEST_HTML);

        return $crawler;
    }

    /** @test */
    public function it_filters_with_first_pseudo_class()
    {
        $crawler = $this->makeCrawler();
        $result = $crawler->filter('.item:first');

        $this->assertCount(1, $result);
        $this->assertEquals('Alpha', $result->text());
    }

    /** @test */
    public function it_filters_with_last_pseudo_class()
    {
        $crawler = $this->makeCrawler();
        $result = $crawler->filter('.item:last');

        $this->assertCount(1, $result);
        $this->assertEquals('Delta', $result->text());
    }

    /** @test */
    public function it_filters_with_eq_pseudo_class()
    {
        $crawler = $this->makeCrawler();
        $result = $crawler->filter('.item:eq(1)');

        $this->assertCount(1, $result);
        $this->assertEquals('Beta', $result->text());
    }

    /** @test */
    public function it_filters_with_eq_pseudo_class_at_zero()
    {
        $crawler = $this->makeCrawler();
        $result = $crawler->filter('.item:eq(0)');

        $this->assertCount(1, $result);
        $this->assertEquals('Alpha', $result->text());
    }

    /** @test */
    public function it_filters_with_chained_pseudo_class_and_trailing_selector()
    {
        $crawler = $this->makeCrawler();
        $result = $crawler->filter('.parent:first .inner');

        $this->assertCount(1, $result);
        $this->assertEquals('Hello', $result->text());
    }

    /** @test */
    public function it_falls_back_to_parent_filter_for_standard_selectors()
    {
        $crawler = $this->makeCrawler();
        $result = $crawler->filter('.item');

        $this->assertCount(4, $result);
    }

    /** @test */
    public function it_removes_nodes_from_dom()
    {
        $crawler = $this->makeCrawler();
        $toRemove = $crawler->filter('.stop');
        $toRemove->remove();

        $remaining = $crawler->filter('.item');
        $this->assertCount(3, $remaining);
    }

    /** @test */
    public function it_returns_parent_node()
    {
        $crawler = $this->makeCrawler();
        $child = $crawler->filter('.child');
        $parent = $child->parent();

        $this->assertInstanceOf(Crawler::class, $parent);
        $this->assertTrue($parent->filter('.child')->count() > 0);
    }
}
