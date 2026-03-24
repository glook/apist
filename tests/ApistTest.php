<?php

namespace glook\apist\tests;

use glook\apist\Apist;
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
        $this->resource = new TestApi;
    }

    /** @test */
    public function it_registers_new_resource()
    {
        $this->assertInstanceOf(Apist::class, $this->resource);
    }

}
