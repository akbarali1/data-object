<?php
declare(strict_types=1);

namespace Akbarali\DataObject;

use Illuminate\Support\Collection;

/**
 * Created by PhpStorm.
 * Filename: DataObjectCollection.php
 * Project Name: data-object
 * Author: akbarali
 * Date: 31/01/2024
 * Time: 14:51
 * GitHub: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class DataObjectCollection
{

    /**
     * @var Collection
     */
    public Collection $items;
    public int        $totalCount;
    public int        $limit;
    public int        $page;

    public function __construct(Collection $items, int $totalCount, int $limit, int $page)
    {
        $this->items      = $items;
        $this->totalCount = $totalCount;
        $this->limit      = $limit;
        $this->page       = $page;
    }
}
