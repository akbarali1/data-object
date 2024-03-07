<?php

namespace Akbarali\DataObject;

use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * Filename: DataObjectContract.php
 * Project Name: data-object
 * Author: akbarali
 * Date: 31/01/2024
 * Time: 14:51
 * GitHub: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
interface DataObjectContract
{

    /**
     * @param Model $model
     * @return static
     */
    public static function createFromEloquentModel(Model $model): static;

    /**
     * @param array $model
     * @return static
     */
    public static function createFromArray(array $model): static;

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function toSnakeArray(bool $trim_nulls = false): array;

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function toArray(bool $trim_nulls = false): array;

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function all(bool $trim_nulls = false): array;


}
