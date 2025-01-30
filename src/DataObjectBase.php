<?php
declare(strict_types=1);

namespace Akbarali\DataObject;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonException;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Class DataObjectBase
 * Abstract class to handle data transformations.
 *
 * @package Akbarali\DataObject
 */
abstract class DataObjectBase implements DataObjectContract
{
	protected array $_parameters = [];
	
	protected function prepare(): void {}
	
	/**
	 * Get a value from the given array.
	 */
	protected function getValue(array $parameters, string $field, mixed $defaultValue = null): mixed
	{
		return $parameters[$field] ?? $parameters[Str::snake($field)] ?? $defaultValue;
	}
	
	/**
	 * Creates an instance from an array.
	 */
	public static function createFromArray(array $model): static
	{
		$fields             = DOCache::resolve(static::class, static fn() => self::getPublicProperties());
		$class              = new static();
		$class->_parameters = $model;
		
		foreach ($fields as $field => $property) {
			$class->setPropertyValue($property, $field);
		}
		
		$class->prepare();
		
		return $class;
	}
	
	/**
	 * Creates an instance from an Eloquent Model.
	 */
	public static function createFromEloquentModel(Model $model): static
	{
		return self::createFromArray($model->toArray());
	}
	
	/**
	 * Creates an instance from a JSON string.
	 *
	 * @throws JsonException
	 */
	public static function createFromJson(string $json): static
	{
		return self::createFromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
	}
	
	/**
	 * Wrapper functions for `createFromArray`.
	 */
	public static function fromModel(Model $model): static
	{
		return self::createFromEloquentModel($model);
	}
	
	public static function fromArray(array $model): static
	{
		return self::createFromArray($model);
	}
	
	public static function fromJson(string $json): static
	{
		return self::createFromJson($json);
	}
	
	/**
	 * Convert object properties to an array.
	 */
	public function toArray(bool $trim_nulls = false): array
	{
		return $this->extractProperties($trim_nulls);
	}
	
	/**
	 * Convert object properties to a snake_case array.
	 */
	public function toSnakeArray(bool $trim_nulls = false): array
	{
		return $this->extractProperties($trim_nulls, true);
	}
	
	/**
	 * Alias for `toArray`
	 */
	public function all(bool $trim_nulls = false): array
	{
		return $this->toArray($trim_nulls);
	}
	
	/**
	 * Convert object to array and remove specific keys.
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
	 * Generate class property definitions from an array.
	 */
	public static function arrayToClassProperty(array $array, bool $camelCase = true): string
	{
		return implode(PHP_EOL, array_map(static function ($key, $value) use ($camelCase) {
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
				
				return 'public '.$type.' $'.($camelCase ? Str::camel($key) : $key).';';
			}, array_keys($array), $array)).PHP_EOL;
	}
	
	/**
	 * Generate class properties dynamically.
	 */
	public static function createProperty(mixed $model, bool $camelCase = false): string
	{
		$data = match (true) {
			is_array($model)                                      => $model,
			$model instanceof Model                               => $model->toArray(),
			is_object($model) && method_exists($model, 'toArray') => $model->toArray(),
			default                                               => throw new DataObjectException('Invalid model type', DataObjectException::INVALID_MODEL_TYPE)
		};
		
		return self::arrayToClassProperty($data, $camelCase);
	}
	
	/**
	 * Retrieve public properties using reflection.
	 */
	private static function getPublicProperties(): array
	{
		$class      = new ReflectionClass(static::class);
		$properties = [];
		
		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
			if (!$property->isStatic()) {
				$properties[$property->getName()] = $property;
			}
		}
		
		return $properties;
	}
	
	/**
	 * Set a property value dynamically.
	 */
	private function setPropertyValue(ReflectionProperty $property, string $field): void
	{
		$value = $this->_parameters[$field] ?? $this->_parameters[Str::snake($field)] ?? $property->getDefaultValue() ?? null;
		
		$type = $property->getType();
		if ($type instanceof ReflectionUnionType) {
			$types = $type->getTypes();
			if (count($types) === 2 && $types[1]->getName() === 'array') {
				$className = $types[0]->getName();
				$value     = class_exists($className) && is_subclass_of($className, self::class) ? array_map(static fn($item) => $className::fromArray($item), $value) : [];
			}
		} elseif (is_array($value) && class_exists($type?->getName()) && is_subclass_of($type->getName(), self::class)) {
			$value = $type->getName()::fromArray($value);
		} elseif (class_exists($type?->getName())) {
			$className = $type->getName();
			if (!($value instanceof $className) && is_subclass_of($className, self::class)) {
				$value = new $className($value);
				if ($value instanceof Carbon) {
					$value->setTimezone(config('app.timezone'));
				}
			}
		}
		
		$property->setAccessible(true);
		$property->setValue($this, $value);
	}
	
	/**
	 * Extract object properties into an array.
	 */
	private function extractProperties(bool $trim_nulls, bool $snakeCase = false): array
	{
		$data = [];
		
		foreach (self::getPublicProperties() as $property) {
			$value = $property->getValue($this);
			if ($trim_nulls && is_null($value)) {
				continue;
			}
			$key        = $snakeCase ? Str::snake($property->getName()) : $property->getName();
			$data[$key] = $value;
		}
		
		return $data;
	}
}