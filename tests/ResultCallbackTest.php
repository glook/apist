<?php

namespace glook\apist\tests;

use glook\apist\ApistMethod;
use glook\apist\Crawler;
use glook\apist\Selectors\ResultCallback;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ResultCallbackTest extends TestCase
{
    public const TEST_HTML = '<html><body>
<p class="hello">World</p>
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

    protected function makeNode(string $selector): Crawler
    {
        return $this->apistMethod->getCrawler()->filter($selector);
    }

    /** @test */
    public function it_returns_method_name_and_arguments()
    {
        $cb = new ResultCallback('text', ['arg1']);
        $this->assertEquals('text', $cb->getMethodName());
        $this->assertEquals(['arg1'], $cb->getArguments());
    }

    /** @test */
    public function it_calls_apist_filter_method()
    {
        $cb = new ResultCallback('text', []);
        $node = $this->makeNode('.hello');
        $result = $cb->apply($node, $this->apistMethod);
        $this->assertEquals('World', $result);
    }

    /** @test */
    public function it_calls_apist_filter_attr_method()
    {
        $cb = new ResultCallback('attr', ['class']);
        $node = $this->makeNode('.hello');
        $result = $cb->apply($node, $this->apistMethod);
        $this->assertEquals('hello', $result);
    }

    /** @test */
    public function it_calls_resource_method()
    {
        $cb = new ResultCallback('customMethod', []);
        $node = $this->makeNode('.hello');
        $result = $cb->apply($node, $this->apistMethod);
        $this->assertEquals('World_custom', $result);
    }

    /** @test */
    public function it_calls_global_function_on_text_node()
    {
        $cb = new ResultCallback('strtoupper', []);
        $node = $this->makeNode('.hello');
        $result = $cb->apply($node, $this->apistMethod);
        $this->assertEquals('WORLD', $result);
    }

    /** @test */
    public function it_calls_global_function_on_string_node()
    {
        $cb = new ResultCallback('strtoupper', []);
        $result = $cb->apply('hello', $this->apistMethod);
        $this->assertEquals('HELLO', $result);
    }

    /** @test */
    public function it_applies_to_array_of_nodes()
    {
        $cb = new ResultCallback('strtoupper', []);
        $result = $cb->apply(['hello', 'world'], $this->apistMethod);
        $this->assertEquals(['HELLO', 'WORLD'], $result);
    }

    /** @test */
    public function it_throws_for_unknown_method()
    {
        $cb = new ResultCallback('nonExistentMethod12345', []);
        $node = $this->makeNode('.hello');
        $this->expectException(InvalidArgumentException::class);
        $cb->apply($node, $this->apistMethod);
    }

    /** @test */
    public function it_handles_else_with_false_becomes_true_and_executes_blueprint()
    {
        $cb = new ResultCallback('else', ['executed']);
        // false → inverted to true → then(true) executes blueprint
        $result = $cb->apply(false, $this->apistMethod);
        $this->assertEquals('executed', $result);
    }

    /** @test */
    public function it_handles_else_with_true_becomes_false_and_returns_false()
    {
        $cb = new ResultCallback('else', ['should_not_execute']);
        // true → inverted to false → then(false) returns false
        $result = $cb->apply(true, $this->apistMethod);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_else_with_non_bool_does_not_invert()
    {
        $cb = new ResultCallback('else', ['ignored']);
        // non-bool string → not inverted, then('string') returns 'string' (not true)
        $result = $cb->apply('some_string', $this->apistMethod);
        $this->assertEquals('some_string', $result);
    }
}
