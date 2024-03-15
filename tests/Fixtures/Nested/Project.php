<?php
declare(strict_types=1);

namespace Tests\Fixtures\Nested;

use Akbarali\DataObject\DataObjectBase;

class Project extends DataObjectBase
{
    public $title;

    public $domain;

    /** @var array<\Tests\Fixtures\Nested\Developer> */
    public $developers;

    protected function castDevelopers(array $developers): array
    {
        return array_map(static function (array $developer) {
            return Developer::fromArray($developer);
        }, $developers);
    }
}
