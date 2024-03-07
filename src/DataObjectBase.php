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
     * @param array $model
     * @return static
     */
    public static function createFromArray(array $model): static
    {
        $fields = DOCache::resolve(static::class, static function () {
            $class  = new \ReflectionClass(static::class);
            $fields = [];
            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                if ($reflectionProperty->isStatic()) {
                    continue;
                }
                $field          = $reflectionProperty->getName();
                $fields[$field] = $reflectionProperty;
            }

            return $fields;
        });
        $class  = new static();
        /** @var array|\ReflectionProperty[] $fields */
        foreach ($fields as $field => $validator) {
            $value = ($model[$field] ?? $model[Str::snake($field)] ?? $validator->getDefaultValue() ?? null);
            if ($validator->getType() instanceof ReflectionUnionType) {
                $types = $validator->getType()->getTypes();
                //array data objectlardan tashkil topgan bo`lsa
                if (!is_null($types) && !is_null($value) && count($types) === 2 && $types[1]->getName() === 'array') {
                    $dataObjectName = $types[0]->getName();
                    if (class_exists($dataObjectName) && new $dataObjectName instanceof DataObjectBase) {
                        $value = array_map(static fn($item) => $dataObjectName::createFromArray($item), $value);
                    }
                } else {
                    $value = [];
                }
                //DataObjectBase classdan tashkil topgan bo`lsa
            } elseif (is_array($value) && !is_null($validator->getType()) && class_exists($validator->getType()->getName())) {
                $dataObject = $validator->getType()->getName();
                if (class_exists($dataObject) && new $dataObject instanceof DataObjectBase) {
                    $value = $dataObject::createFromArray($value);
                }
            } elseif (!is_null($validator->getType()) && class_exists($validator->getType()->getName())) {
                $dataObject = $validator->getType()->getName();
                if (class_exists($dataObject) && !(new $dataObject instanceof DataObjectBase)) {
                    $newClass = $validator->getType()->getName();
                    $value    = new $newClass($value);
                }
            }
            $validator->setAccessible(true);
            $validator->setValue($class, $value);
        }

        return $class;
    }

    /**
     * @param Model $model
     * @return static
     */
    public static function createFromEloquentModel(Model $model): static
    {
        return self::createFromArray($model->toArray());
    }

    /**
     * @param string $json
     * @return static
     * @throws \JsonException
     */
    public static function createFromJson(string $json): static
    {
        return self::createFromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function toArray(bool $trim_nulls = false): array
    {
        $data = [];
        try {
            $class      = new \ReflectionClass(static::class);
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
            $class      = new \ReflectionClass(static::class);
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
