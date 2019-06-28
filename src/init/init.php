<?php
namespace hyf\init;

use hyf\init\job\table;
use hyf\init\job\start;
use hyf\init\job\timer;
use hyf\init\job\server;
use hyf\init\job\timerPool;

class init
{
    protected static $init = [
        'http' => [
            table::class,
            start::class,
            timer::class,
            server::class
        ],
        'timer' => [
            table::class,
            start::class,
            timerPool::class
        ]
    ];

    public static function Run($type = 'http')
    {
        foreach (self::$init[$type] as $class) {
            call_user_func("{$class}::run");
        }
    }
}
