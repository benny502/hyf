<?php
namespace hyf\init;

use hyf\init\job\start;
use hyf\init\job\timer;
use hyf\init\job\server;
use hyf\init\job\timerPool;

class init
{
    protected static $init = [
        'http' => [
            start::class,
            timer::class,
            server::class
        ],
        'timer' => [
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
