<?php
declare(strict_types=1);

namespace Tests\Fixtures\Nested;

use Akbarali\DataObject\DataObjectBase;

class Company extends DataObjectBase
{
    public $title;

    /** @var array<\Tests\Fixtures\Nested\Project> */
    public $projects;

    protected function castProjects(array $projects): array
    {
        return array_map(static function (array $project) {
            return Project::fromArray($project);
        }, $projects);
    }
}
