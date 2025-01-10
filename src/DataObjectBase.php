<?php

namespace Akbarali\DataObject;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
	protected array $_parameters = [];
	
	protected function prepare(): void {}
	
	/**
	 * @param  array  $parameters
	 * @param         $field
	 * @param         $defaultValue
	 * @return mixed
	 */
	protected function getValue(array $parameters, $field, $defaultValue = null): mixed
	{
		return $parameters[$field] ?? $parameters[Str::snake($field)] ?? $defaultValue;
	}
	
	/**
	 * @param  array  $model
	 * @return static
	 */
	public static function createFromArray(array $model): static
	{
		$fields                = DOCache::resolve(static::class, static fn(): array => static::getClassFields());
		$instance              = new static();
		$instance->_parameters = $model;
		foreach ($fields as $field => $property) {
			$value = $instance->resolveFieldValue($model, $field, $property);
			$property->setAccessible(true);
			$property->setValue($instance, $value);
		}
		
		$instance->prepare();
		
		return $instance;
	}
	
	/**
	 * @param  Model  $model
	 * @return static
	 */
	public static function createFromEloquentModel(Model $model): static
	{
		return static::createFromArray($model->toArray());
	}
	
	/**
	 * @param  string  $json
	 * @throws \JsonException
	 * @return static
	 */
	public static function createFromJson(string $json): static
	{
		return static::createFromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * @param  Model  $model
	 * @return static
	 */
	public static function fromModel(Model $model): static
	{
		return static::createFromEloquentModel($model);
	}
	
	/**
	 * @param  array  $model
	 * @return static
	 */
	public static function fromArray(array $model): static
	{
		return static::createFromArray($model);
	}
	
	/**
	 * @param  string  $json
	 * @throws \JsonException
	 * @return static
	 */
	public static function fromJson(string $json): static
	{
		return static::createFromJson($json);
	}
	
	/**
	 * @param  bool  $trim_nulls
	 * @return array
	 */
	public function toArray(bool $trim_nulls = false): array
	{
		return $this->generateArray($trim_nulls);
	}
	
	/**
	 * @param  bool  $trim_nulls
	 * @return array
	 */
	public function toSnakeArray(bool $trim_nulls = false): array
	{
		return $this->generateArray($trim_nulls, true);
	}
	
	/**
	 * @param  string|array  $forget
	 * @param  bool          $trim_nulls
	 * @return array
	 */
	public function toArrayForgetProperty(string|array $forget, bool $trim_nulls = false): array
	{
		$array = $this->toArray($trim_nulls);
		foreach ((array) $forget as $key) {
			unset($array[$key]);
		}
		
		return $array;
	}
	
	/**
	 * @param  array  $array
	 * @param  bool   $camelCase
	 * @return string
	 */
	public static function arrayToClassProperty(array $array, bool $camelCase = true): string
	{
		$properties = [];
		foreach ($array as $key => $value) {
			$type = match (gettype($value)) {
				'integer' => 'int',
				'double'  => 'float',
				'string'  => 'string',
				'array'   => 'array',
				default   => '?string',
			};
			if (str_contains($key, 'id')) {
				$type = 'readonly int';
			}
			$key          = $camelCase ? Str::camel($key) : $key;
			$properties[] = "public $type \$$key;";
		}
		
		return implode(PHP_EOL, $properties);
	}
	
	/**
	 * @param  mixed  $model
	 * @param  bool   $camelCase
	 * @throws DataObjectException
	 * @return string
	 */
	public static function createProperty(mixed $model, bool $camelCase = false): string
	{
		if (is_array($model)) {
			return static::arrayToClassProperty($model, $camelCase);
		}
		
		if ($model instanceof Model) {
			return static::arrayToClassProperty($model->toArray(), $camelCase);
		}
		
		if (is_object($model) && method_exists($model, 'first') && method_exists($model, 'toArray')) {
			return static::arrayToClassProperty($model->first()->toArray(), $camelCase);
		}
		
		if (is_object($model) && method_exists($model, 'toArray')) {
			return static::arrayToClassProperty($model->toArray(), $camelCase);
		}
		
		throw new DataObjectException('Invalid model type', DataObjectException::INVALID_MODEL_TYPE);
	}
	
	/**
	 * @return array
	 */
	private static function getClassFields(): array
	{
		$class  = new \ReflectionClass(static::class);
		$fields = [];
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			if (!$property->isStatic()) {
				$fields[$property->getName()] = $property;
			}
		}
		
		return $fields;
	}
	
	/**
	 * @param  array                $model
	 * @param  string               $field
	 * @param  \ReflectionProperty  $property
	 * @return mixed
	 */
	private function resolveFieldValue(array $model, string $field, \ReflectionProperty $property): mixed
	{
		$value = $model[$field] ?? $model[Str::snake($field)] ?? $property->getDefaultValue() ?? null;
		
		$type = $property->getType();
		if ($type instanceof \ReflectionUnionType) {
			return $this->handleUnionType($type, $value);
		}
		
		if (is_array($value) && $type && class_exists($type->getName()) && is_subclass_of($type->getName(), static::class)) {
			return $type->getName()::fromArray($value);
		}
		
		if ($type && class_exists($type->getName())) {
			return $this->instantiateObject($type->getName(), $value);
		}
		
		return $value;
	}
	
	/**
	 * @param  \ReflectionUnionType  $type
	 * @param  mixed                 $value
	 * @return mixed
	 */
	private function handleUnionType(\ReflectionUnionType $type, mixed $value): mixed
	{
		$types = $type->getTypes();
		if (count($types) === 2 && $types[1]->getName() === 'array') {
			$dataObject = $types[0]->getName();
			if (class_exists($dataObject) && is_subclass_of($dataObject, static::class) && is_array($value)) {
				return array_map(static fn($item) => $dataObject::fromArray($item), $value);
			}
			
			return [];
		}
		
		return $value;
	}
	
	/**
	 * @param  string  $className
	 * @param  mixed   $value
	 * @return mixed
	 */
	private function instantiateObject(string $className, mixed $value): mixed
	{
		if (!is_object($value)) {
			$value = new $className($value);
		}
		if ($value instanceof Carbon) {
			$value->setTimezone(config('app.timezone'));
		}
		
		return $value;
	}
	
	/**
	 * @param  bool  $trim_nulls
	 * @param  bool  $snakeCase
	 * @return array
	 */
	private function generateArray(bool $trim_nulls, bool $snakeCase = false): array
	{
		$data       = [];
		$properties = static::getClassFields();
		foreach ($properties as $name => $property) {
			$value = $property->getValue($this);
			if ($trim_nulls && $value === null) {
				continue;
			}
			$key        = $snakeCase ? Str::snake($name) : $name;
			$data[$key] = $value;
		}
		
		return $data;
	}
}
