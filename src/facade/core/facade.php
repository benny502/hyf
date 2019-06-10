<?php
namespace hyf\facade\core;

use hyf\container\core\container;

class facade
{

    public static function getInstance($class)
    {
        return container::getInstance()[$class];
    }

    public static function getFacadeAccessor()
    {
    }

    public static function __callstatic($method, $args)
    {
        $instance = static::getInstance(static::getFacadeAccessor());
        return call_user_func_array([$instance, $method], $args);
    }
}