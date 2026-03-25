<?php

namespace glook\apist\tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApistMethodTest extends TestCase
{
    /**
     * @var TestApi
     */
    protected $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = new TestApi();

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], file_get_contents(__DIR__ . '/stub/index.html')),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $this->resource->setGuzzle($client);
    }

    /** @test */
    public function it_parses_result_by_blueprint()
    {
        $result = $this->resource->index();

        $this->assertEquals('Моя лента', $result['title']);
        $this->assertEquals('http://tmtm.ru/', $result['copyright']);
        $this->assertCount(10, $result['posts']);
    }

    /** @test */
    public function it_returns_null_if_element_not_found()
    {
        $result = $this->resource->element_not_found();

        $this->assertEquals(['title' => null], $result);
    }

    /** @test */
    public function it_parses_non_array_blueprint()
    {
        $result = $this->resource->non_array_blueprint();

        $this->assertEquals('Моя лента', $result);
    }

    /** @test */
    public function it_must_return_string()
    {
        $result = $this->resource->plain_return();

        $this->assertIsString($result);
    }

    /** @test */
    public function it_sets_and_gets_http_method()
    {
        $result = $this->resource->getLastMethod();
        // Before any request, lastMethod is null
        $this->assertNull($result);

        $this->resource->plain_return();

        $lastMethod = $this->resource->getLastMethod();
        $this->assertEquals('GET', $lastMethod->getMethod());
    }

    /** @test */
    public function it_parses_content_without_http_request()
    {
        $html = '<html><body><h1>Direct Parse</h1></body></html>';
        $result = $this->resource->parseContent($html, [
            'heading' => \glook\apist\Apist::filter('h1'),
        ]);

        $this->assertEquals('Direct Parse', $result['heading']);
    }

    /** @test */
    public function it_returns_raw_content_with_null_blueprint()
    {
        $html = '<html><body><p>Raw</p></body></html>';
        $result = $this->resource->parseContent($html, null);

        $this->assertStringContainsString('Raw', $result);
    }

    /** @test */
    public function default_before_parse_returns_content_unchanged()
    {
        $html = '<html><body><h1>Hello</h1></body></html>';
        $result = $this->resource->parseContent($html, [
            'heading' => \glook\apist\Apist::filter('h1'),
        ]);

        $this->assertEquals('Hello', $result['heading']);
    }

    /** @test */
    public function overridden_before_parse_transforms_content_before_parsing()
    {
        $api = new class extends \glook\apist\Apist {
            protected ?string $baseUrl = 'http://example.com';

            public function beforeParse(string $content): string
            {
                return str_replace('World', 'Replaced', $content);
            }

            public function parseContent(string $content, $blueprint)
            {
                return $this->parse($content, $blueprint);
            }
        };

        $html = '<html><body><h1>World</h1></body></html>';
        $result = $api->parseContent($html, [
            'heading' => \glook\apist\Apist::filter('h1'),
        ]);

        $this->assertEquals('Replaced', $result['heading']);
    }

    /** @test */
    public function before_parse_is_called_during_http_request()
    {
        $api = new class extends \glook\apist\Apist {
            protected ?string $baseUrl = 'http://example.com';
            public bool $beforeParseCalled = false;

            public function beforeParse(string $content): string
            {
                $this->beforeParseCalled = true;
                return $content;
            }

            public function fetchIndex()
            {
                return $this->get('/');
            }
        };

        $mock = new MockHandler([
            new Response(200, [], '<html><body></body></html>'),
        ]);
        $api->setGuzzle(new Client(['handler' => HandlerStack::create($mock)]));

        $api->fetchIndex();

        $this->assertTrue($api->beforeParseCalled);
    }
}
