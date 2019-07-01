<?php
namespace hyf\component\memory\table;

class table
{

    public static $table = [];

    public static function __callStatic($method, $args)
    {
        if (!isset(self::$table[$method])) {
            return NULL;
        }
        return self::$table[$method];
    }
}
