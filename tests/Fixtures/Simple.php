<?php
declare(strict_types=1);

namespace Tests\Fixtures;

use Akbarali\DataObject\DataObjectBase;

class Simple extends DataObjectBase
{
    public $foo;

    protected $bar;

    private $baz;

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }

    public function getBaz()
    {
        return $this->baz;
    }
}
