<?php

namespace glook\apist\tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApistMethodErrorTest extends TestCase
{
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
    public function it_returns_error_response_on_connect_exception()
    {
        $request = new Request('GET', 'http://example.com/');
        $exception = new ConnectException('Connection refused', $request);

        $this->resource->setGuzzle($this->mockClient([$exception]));
        $result = $this->resource->plain_return();

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('status', $result['error']);
        $this->assertArrayHasKey('reason', $result['error']);
        $this->assertStringContainsString('Connection refused', $result['error']['reason']);
    }

    /** @test */
    public function it_returns_error_response_on_request_exception_without_response()
    {
        $request = new Request('GET', 'http://example.com/');
        $exception = new RequestException('Bad request', $request);

        $this->resource->setGuzzle($this->mockClient([$exception]));
        $result = $this->resource->plain_return();

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Bad request', $result['error']['reason']);
    }

    /** @test */
    public function it_returns_error_response_on_request_exception_with_response()
    {
        $request = new Request('GET', 'http://example.com/');
        $response = new Response(404, [], '');
        $exception = new RequestException('Not Found', $request, $response);

        $this->resource->setGuzzle($this->mockClient([$exception]));
        $result = $this->resource->plain_return();

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(404, $result['error']['status']);
        $this->assertEquals('Not Found', $result['error']['reason']);
    }

    /** @test */
    public function it_returns_response_object_after_successful_request()
    {
        $htmlContent = '<html><body><h1>Test</h1></body></html>';
        $this->resource->setGuzzle($this->mockClient([
            new Response(200, [], $htmlContent),
        ]));

        $this->resource->plain_return();

        $lastMethod = $this->resource->getLastMethod();
        $this->assertNotNull($lastMethod);
        $response = $lastMethod->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_stores_url_as_string_in_error_response()
    {
        $request = new Request('GET', 'http://example.com/test');
        $exception = new ConnectException('Timeout', $request);

        $this->resource->setGuzzle($this->mockClient([$exception]));
        $result = $this->resource->plain_return();

        $this->assertIsString($result['url']);
        $this->assertStringContainsString('example.com', $result['url']);
    }
}
