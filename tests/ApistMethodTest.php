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
        $this->resource = new TestApi;

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

}
