<?php
declare(strict_types=1);

namespace Tests\Fixtures\Nested;

use Akbarali\DataObject\DataObjectBase;

class Company extends DataObjectBase
{
    public string $title;

    /** @var array<\Tests\Fixtures\Nested\Project> */
    public array $projects;

}
