<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $foo = 'Foo';

    protected string $bar = 'Bar';

    protected string $baz = 'Baz';

    protected string $baq = 'Baq';
}
