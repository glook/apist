<?php

namespace glook\apist\tests\Yaml;

use glook\apist\Apist;
use glook\apist\Yaml\YamlApist;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class YamlApistTest extends TestCase
{
    protected function mockClient(array $queue): Client
    {
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);
        return new Client(['handler' => $handler]);
    }

    /** @test */
    public function it_constructs_without_yaml()
    {
        $api = new YamlApist(null);
        $this->assertInstanceOf(Apist::class, $api);
    }

    /** @test */
    public function it_constructs_from_yaml_content()
    {
        $yaml = "baseUri: http://example.com\nmyMethod:\n  url: /page\n  blueprint:\n    title: .title | text\n";
        $api = new YamlApist($yaml);
        $this->assertInstanceOf(Apist::class, $api);
        $this->assertEquals('http://example.com', $api->getBaseUri());
    }

    /** @test */
    public function it_throws_for_undefined_method_when_no_parser()
    {
        $api = new YamlApist(null);
        $this->expectException(\InvalidArgumentException::class);
        $api->someUndefinedMethod();
    }

    /** @test */
    public function it_dispatches_method_call_via_yaml()
    {
        $html = '<html><body><h1 class="title">My Title</h1></body></html>';
        $yaml = "baseUri: http://example.com\nmyMethod:\n  url: /page\n  blueprint:\n    title: .title | text\n";

        $api = new YamlApist($yaml);
        $api->setGuzzle($this->mockClient([
            new Response(200, [], $html),
        ]));

        $result = $api->myMethod();

        $this->assertArrayHasKey('title', $result);
        $this->assertEquals('My Title', $result['title']);
    }

    /** @test */
    public function it_uses_get_as_default_http_method()
    {
        $html = '<html><body></body></html>';
        $yaml = "baseUri: http://example.com\nmyMethod:\n  url: /page\n  blueprint:\n    title: .title | text\n";

        $api = new YamlApist($yaml);
        $api->setGuzzle($this->mockClient([
            new Response(200, [], $html),
        ]));

        $api->myMethod();

        $lastMethod = $api->getLastMethod();
        $this->assertEquals('GET', $lastMethod->getMethod());
    }

    /** @test */
    public function it_uses_http_method_from_yaml()
    {
        $html = '<html><body></body></html>';
        $yaml = "baseUri: http://example.com\nmyMethod:\n  url: /page\n  method: post\n  blueprint:\n    title: .title | text\n";

        $api = new YamlApist($yaml);
        $api->setGuzzle($this->mockClient([
            new Response(200, [], $html),
        ]));

        $api->myMethod();

        $lastMethod = $api->getLastMethod();
        $this->assertEquals('POST', $lastMethod->getMethod());
    }

    /** @test */
    public function it_passes_arguments_to_yaml_method()
    {
        $html = '<html><body><h1 class="title">Test</h1></body></html>';
        $yaml = "baseUri: http://example.com\nmyMethod:\n  url: /page/\$1\n  blueprint:\n    title: .title | text\n";

        $api = new YamlApist($yaml);
        $api->setGuzzle($this->mockClient([
            new Response(200, [], $html),
        ]));

        $result = $api->myMethod('articles');
        $this->assertEquals('Test', $result['title']);
    }
}
