<?php
namespace hyf\container;

use hyf\container\core\container;
use hyf\component\io\output;
use hyf\component\memory\table\table;
use hyf\component\db\mysql\mysql;
use hyf\component\db\redis\redis;

class binds
{

    // 默认bind列表(框架级)
    protected static $binds = [
        'http' => [
            'table' => table::class, 
            'output' => output::class, 
            'mysql' => mysql::class, 
            'redis' => redis::class
        ], 
        'timer' => [
            'table' => table::class, 
            'mysql' => mysql::class, 
            'redis' => redis::class
        ]
    ];

    public static function Run($type = 'http')
    {
        foreach (self::$binds[$type] as $key => $value) {
            container::getInstance()[md5($key)] = function () use ($value) {
                return new $value();
            };
        }
    }
}
