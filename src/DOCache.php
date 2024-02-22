<?php

namespace Akbarali\DataObject;

class DOCache
{
    /** @var array */
    private static array $cache = [];

    public static function resolve(string $class, \Closure $closure): array
    {
        if (!isset(self::$cache[$class])) {
            self::$cache[$class] = $closure();
        }

        return self::$cache[$class];
    }
}