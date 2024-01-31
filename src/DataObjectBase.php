<?php

namespace Akbarali\DataObject;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionUnionType;

/**
 * Created by PhpStorm.
 * Filename: DataObjectBase.php
 * Project Name: data-object
 * Author: akbarali
 * Date: 31/01/2024
 * Time: 14:51
 * GitHub: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
abstract class DataObjectBase implements DataObjectContract
{
    /**
     * @var array $_parameters
     */
    protected array $_parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->_parameters = $parameters;
        try {

            $class  = new \ReflectionClass(static::class);
            $fields = [];

            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                if ($reflectionProperty->isStatic()) {
                    continue;
                }
                $field          = $reflectionProperty->getName();
                $fields[$field] = $reflectionProperty;
            }
            foreach ($fields as $field => $validator) {
                $value = ($parameters[$field] ?? $parameters[Str::snake($field)] ?? $validator->getDefaultValue() ?? null);
                if ($validator->getType() instanceof ReflectionUnionType) {
                    $types = $validator->getType()->getTypes();
                    if (!is_null($types) && !is_null($value) && count($types) === 2 && $types[1]->getName() === 'array') {
                        $dataObjectName = $types[0]->getName();
                        $value          = array_map(static fn($item) => new $dataObjectName($item), $value);
                    } else {
                        $value = [];
                    }
                } elseif (is_array($value) && count($value) > 0 && !is_null($validator->getType()) && class_exists($validator->getType()->getName())) {
                    $dataObject = $validator->getType()->getName();
                    $value      = new $dataObject($value);
                } elseif (!is_null($validator->getType()) && class_exists($validator->getType()->getName())) {
                    $value = null;
                }

                $this->{$field} = $value;
                unset($parameters[$field]);
            }
            $this->prepare();
        } catch (\Exception $exception) {

        }
    }

    protected function prepare(): void {}

    /**
     * @param array $parameters
     * @param       $field
     * @param       $defaultValue
     * @return mixed
     */
    protected function getValue(array $parameters, $field, $defaultValue = null): mixed
    {
        return ($parameters[$field] ?? $parameters[Str::snake($field)] ?? $defaultValue);
    }

    /**
     * @param Model $model
     * @return static
     */
    public static function createFromEloquentModel(Model $model): static
    {
        return new static($model->toArray());
    }

    /**
     * @param array $model
     * @return static
     */
    public static function createFromArray(array $model): static
    {
        return new static($model);
    }

    /**
     * @param string $json
     * @return static
     * @throws \JsonException
     */
    public static function createFromJson(string $json): static
    {
        return new static(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }


    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function toArray(bool $trim_nulls = false): array
    {
        $data = [];

        try {
            $class = new \ReflectionClass(static::class);

            $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($properties as $reflectionProperty) {
                if ($reflectionProperty->isStatic()) {
                    continue;
                }
                $value = $reflectionProperty->getValue($this);
                if ($trim_nulls === true) {
                    if (!is_null($value)) {
                        $data[$reflectionProperty->getName()] = $value;
                    }
                } else {
                    $data[$reflectionProperty->getName()] = $value;
                }
            }
        } catch (\Exception $exception) {

        }

        return $data;
    }

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function toSnakeArray(bool $trim_nulls = false): array
    {
        $data = [];

        try {
            $class = new \ReflectionClass(static::class);

            $properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($properties as $reflectionProperty) {
                if ($reflectionProperty->isStatic()) {
                    continue;
                }
                $value = $reflectionProperty->getValue($this);
                if ($trim_nulls === true) {
                    if (!is_null($value)) {
                        $data[Str::snake($reflectionProperty->getName())] = $value;
                    }
                } else {
                    $data[Str::snake($reflectionProperty->getName())] = $value;
                }
            }
        } catch (\Exception $exception) {

        }

        return $data;
    }

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function all(bool $trim_nulls = false): array
    {
        return $this->toArray($trim_nulls);
    }
}
