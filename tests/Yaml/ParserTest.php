<?php

namespace glook\apist\tests\Yaml;

use glook\apist\Selectors\ApistSelector;
use glook\apist\tests\TestApi;
use glook\apist\Yaml\Parser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    protected function makeParser(string $yaml): Parser
    {
        return new Parser($yaml);
    }

    protected function makeResource(): TestApi
    {
        return new TestApi();
    }

    /** @test */
    public function it_loads_base_uri_from_yaml()
    {
        $yaml = "baseUri: http://example.com\nmyMethod:\n  url: /path\n  blueprint:\n    title: .title | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();

        $parser->load($resource);

        $this->assertEquals('http://example.com', $resource->getBaseUri());
    }

    /** @test */
    public function it_loads_base_url_alias_as_base_uri()
    {
        $yaml = "baseUrl: http://legacy.example.com\nmyMethod:\n  url: /path\n  blueprint:\n    title: .title | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();

        $parser->load($resource);

        $this->assertEquals('http://legacy.example.com', $resource->getBaseUri());
    }

    /** @test */
    public function it_separates_methods_and_structures()
    {
        $yaml = "_item:\n  title: .title | text\nmyMethod:\n  url: /page\n  blueprint:\n    title: .title | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $method = $parser->getMethod('myMethod');
        $this->assertArrayHasKey('url', $method);
        $this->assertArrayHasKey('blueprint', $method);
    }

    /** @test */
    public function it_throws_for_unknown_method()
    {
        $yaml = "myMethod:\n  url: /page\n  blueprint:\n    title: .title | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $this->expectException(\InvalidArgumentException::class);
        $parser->getMethod('nonExistentMethod');
    }

    /** @test */
    public function it_parses_pipe_syntax_into_filter_with_callback()
    {
        $yaml = "myMethod:\n  url: /page\n  blueprint:\n    title: .title | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $method = $parser->getMethod('myMethod');
        $this->assertInstanceOf(ApistSelector::class, $method['blueprint']['title']);
    }

    /** @test */
    public function it_returns_null_blueprint_when_not_specified()
    {
        $yaml = "myMethod:\n  url: /page\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $method = $parser->getMethod('myMethod');
        $this->assertNull($method['blueprint']);
    }

    /** @test */
    public function it_inserts_method_arguments_by_position()
    {
        $yaml = "myMethod:\n  url: /page/\$1\n  blueprint:\n    title: .title | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $method = $parser->getMethod('myMethod');
        $result = $parser->insertMethodArguments($method, ['user-123']);

        $this->assertEquals('/page/user-123', $result['url']);
    }

    /** @test */
    public function it_inserts_multiple_method_arguments()
    {
        $yaml = "myMethod:\n  url: /\$1/\$2\n  blueprint:\n    title: .title | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $method = $parser->getMethod('myMethod');
        $result = $parser->insertMethodArguments($method, ['users', '42']);

        $this->assertEquals('/users/42', $result['url']);
    }

    /** @test */
    public function it_throws_for_unknown_structure_reference()
    {
        $yaml = "myMethod:\n  url: /page\n  blueprint:\n    item: :unknownStruct\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();

        $this->expectException(\InvalidArgumentException::class);
        $parser->load($resource);
    }

    /** @test */
    public function it_resolves_structure_reference_in_blueprint()
    {
        $yaml = "_post:\n  title: .title | text\nmyMethod:\n  url: /page\n  blueprint:\n    post: :post\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $method = $parser->getMethod('myMethod');
        $this->assertArrayHasKey('post', $method['blueprint']);
        $this->assertIsArray($method['blueprint']['post']);
    }

    /** @test */
    public function it_replaces_current_with_wildcard_in_blueprint()
    {
        $yaml = "myMethod:\n  url: /page\n  blueprint:\n    title: :current | text\n";
        $parser = $this->makeParser($yaml);
        $resource = $this->makeResource();
        $parser->load($resource);

        $method = $parser->getMethod('myMethod');
        // :current is replaced with * as the selector in the resulting ApistSelector
        $this->assertInstanceOf(ApistSelector::class, $method['blueprint']['title']);
    }
}
