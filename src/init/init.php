<?php
namespace hyf\init;

use hyf\init\job\memory;
use hyf\init\job\start;
use hyf\init\job\timer;
use hyf\init\job\server;
use hyf\init\job\timerPool;
use hyf\init\job\process;

class init
{
    protected static $init = [
        'http' => [
            memory::class,
            start::class,
            timer::class,
            process::class,
            server::class
        ],
        'timer' => [
            memory::class,
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
