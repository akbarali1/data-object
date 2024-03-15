<?php
declare(strict_types=1);

namespace Tests\Fixtures;

use Akbarali\DataObject\DataObjectBase;

class Simple extends DataObjectBase
{
    public string $foo;

    protected string $bar;

    private string $baz;

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function getBar(): string
    {
        return $this->bar;
    }

    public function getBaz(): string
    {
        return $this->baz;
    }
}
