<?php
namespace hyf\component\memory\queue;

class queue
{
    public static $queue = [];

    public static function __callStatic($method, $args)
    {
        if (!isset(self::$queue[$method])) {
            return NULL;
        }
        return self::$queue[$method];
    }
}
