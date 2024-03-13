<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Fixtures\Simple;
use Tests\TestCase;

class SimpleTest extends TestCase
{
    public function testMake()
    {
        $object = Simple::fromArray([
            'foo' => $this->foo,
            'bar' => $this->bar,
            'baz' => $this->baz,
        ]);

        $this->assertSame($this->foo, $object->foo);

        $this->assertSame($this->foo, $object->getFoo());
        $this->assertSame($this->bar, $object->getBar());

        $this->assertNull($object->getBaz());
    }

    public function testConstruct()
    {
        $object = Simple::fromArray([
            'foo' => $this->foo,
            'bar' => $this->bar,
            'baz' => $this->baz,
        ]);

        $this->assertSame($this->foo, $object->foo);

        $this->assertSame($this->foo, $object->getFoo());
        $this->assertSame($this->bar, $object->getBar());

        $this->assertNull($object->getBaz());
    }

    public function testToArray()
    {
        $object = Simple::fromArray([
            'foo' => $this->foo,
            'bar' => $this->bar,
            'baz' => $this->baz,
        ]);

        $this->assertIsArray($object->toArray());

        $this->assertSame([
            'foo' => $this->foo,
        ], $object->toArray());
    }
}
