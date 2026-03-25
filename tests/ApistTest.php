<?php

namespace glook\apist\tests;

use glook\apist\Apist;
use glook\apist\Selectors\ApistSelector;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ApistTest extends TestCase
{
    /**
     * @var TestApi
     */
    protected $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = new TestApi();
    }

    protected function mockClient(array $queue): Client
    {
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);

        return new Client(['handler' => $handler]);
    }

    /** @test */
    public function it_registers_new_resource()
    {
        $this->assertInstanceOf(Apist::class, $this->resource);
    }

    /** @test */
    public function it_returns_base_uri()
    {
        $this->assertEquals('http://example.com', $this->resource->getBaseUrl());
    }

    /** @test */
    public function it_sets_and_gets_base_uri()
    {
        $this->resource->setBaseUrl('http://new.example.com');
        $this->assertEquals('http://new.example.com', $this->resource->getBaseUrl());
    }

    /** @test */
    public function it_creates_filter_selector()
    {
        $selector = Apist::filter('.foo');
        $this->assertInstanceOf(ApistSelector::class, $selector);
    }

    /** @test */
    public function it_creates_current_selector()
    {
        $selector = Apist::current();
        $this->assertInstanceOf(ApistSelector::class, $selector);
    }

    /** @test */
    public function it_toggles_suppress_exceptions()
    {
        $this->assertTrue($this->resource->isSuppressExceptions());
        $this->resource->setSuppressExceptions(false);
        $this->assertFalse($this->resource->isSuppressExceptions());
    }

    /** @test */
    public function it_throws_when_suppress_exceptions_disabled_and_element_not_found()
    {
        $html = '<html><body><h1>Test</h1></body></html>';
        $this->resource->setGuzzle($this->mockClient([
            new Response(200, [], $html),
        ]));
        $this->resource->setSuppressExceptions(false);

        $this->expectException(InvalidArgumentException::class);
        $this->resource->element_not_found();
    }

    /** @test */
    public function it_returns_last_method_after_execution()
    {
        $html = '<html><body><h1>Test</h1></body></html>';
        $this->resource->setGuzzle($this->mockClient([
            new Response(200, [], $html),
        ]));

        $this->resource->plain_return();

        $lastMethod = $this->resource->getLastMethod();
        $this->assertNotNull($lastMethod);
    }

    /** @test */
    public function it_parses_content_without_http_request()
    {
        $html = '<html><body><h1>Hello</h1></body></html>';
        $result = $this->resource->parseContent($html, [
            'title' => Apist::filter('h1'),
        ]);

        $this->assertEquals('Hello', $result['title']);
    }
}
