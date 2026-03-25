<?php

namespace glook\apist\tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RequestOptionsTest extends TestCase
{
    private TestApi $api;

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = new TestApi();
    }

    /**
     * Build a Guzzle client with history middleware and return a reference to the history container.
     *
     * @param array $queue Guzzle responses to enqueue
     * @param array $history Container that will be populated with transaction records
     */
    private function makeClientWithHistory(array $queue, array &$history): Client
    {
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));

        return new Client(['handler' => $handler]);
    }

    private function htmlResponse(): Response
    {
        return new Response(200, [], '<html><body></body></html>');
    }

    /** @test */
    public function it_works_with_empty_default_options(): void
    {
        $history = [];
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doGet('/');

        $this->assertCount(1, $history);
        $this->assertEquals('GET', $history[0]['request']->getMethod());
    }

    /** @test */
    public function it_merges_default_request_options_into_get(): void
    {
        $history = [];
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'foo']]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doGet('/');

        $this->assertCount(1, $history);
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_merges_default_request_options_into_post(): void
    {
        $history = [];
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'foo']]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doPost('/');

        $this->assertCount(1, $history);
        $this->assertEquals('POST', $history[0]['request']->getMethod());
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_merges_default_request_options_into_put(): void
    {
        $history = [];
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'foo']]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doPut('/');

        $this->assertCount(1, $history);
        $this->assertEquals('PUT', $history[0]['request']->getMethod());
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_merges_default_request_options_into_patch(): void
    {
        $history = [];
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'foo']]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doPatch('/');

        $this->assertCount(1, $history);
        $this->assertEquals('PATCH', $history[0]['request']->getMethod());
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_merges_default_request_options_into_delete(): void
    {
        $history = [];
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'foo']]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doDelete('/');

        $this->assertCount(1, $history);
        $this->assertEquals('DELETE', $history[0]['request']->getMethod());
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_merges_default_request_options_into_head(): void
    {
        $history = [];
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'foo']]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doHead('/');

        $this->assertCount(1, $history);
        $this->assertEquals('HEAD', $history[0]['request']->getMethod());
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_allows_per_call_options_to_override_defaults(): void
    {
        $history = [];
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'default']]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doGet('/', null, ['headers' => ['X-Custom' => 'override']]);

        $this->assertCount(1, $history);
        $this->assertEquals('override', $history[0]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_merges_non_header_options_like_timeout(): void
    {
        $history = [];
        $this->api->setRequestOptions(['timeout' => 30]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doGet('/');

        $this->assertCount(1, $history);
        $this->assertEquals(30, $history[0]['options']['timeout']);
    }

    /** @test */
    public function it_does_not_carry_options_across_separate_calls(): void
    {
        $this->api->setRequestOptions(['headers' => ['X-Custom' => 'foo']]);

        $history = [];
        $this->api->setGuzzle($this->makeClientWithHistory([
            $this->htmlResponse(),
            $this->htmlResponse(),
        ], $history));

        $this->api->doGet('/');
        $this->api->doGet('/');

        $this->assertCount(2, $history);
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Custom'));
        $this->assertEquals('foo', $history[1]['request']->getHeaderLine('X-Custom'));
    }

    /** @test */
    public function it_merges_multiple_default_options(): void
    {
        $history = [];
        $this->api->setRequestOptions([
            'headers' => ['X-Foo' => 'foo', 'X-Bar' => 'bar'],
        ]);
        $this->api->setGuzzle($this->makeClientWithHistory([$this->htmlResponse()], $history));

        $this->api->doGet('/');

        $this->assertCount(1, $history);
        $this->assertEquals('foo', $history[0]['request']->getHeaderLine('X-Foo'));
        $this->assertEquals('bar', $history[0]['request']->getHeaderLine('X-Bar'));
    }
}
