<?php
namespace sts\core\utils;

class LazyLoader
{
    protected static $instances = [];

    public static function get($class)
    {
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }
}
