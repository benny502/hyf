<?php
namespace hyf\component\call;

class call
{
    static public $instance = [];

    public static function __callStatic($name, $arguments)
    {
        $class = '\\application\\' . app_name() . '\\' . str_replace('_', '\\', $name);
        if (empty(self::$instance[md5($class)])) {
            if (!empty($arguments[0])) {
                $ref = new \ReflectionClass($class);
                self::$instance[md5($class)] = $ref->newInstanceArgs($arguments[0]);
            } else {
                self::$instance[md5($class)] = new $class();
            }
        }

        return self::$instance[md5($class)];
    }
}
