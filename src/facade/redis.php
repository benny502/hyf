<?php
namespace hyf\facade;

use hyf\facade\core\facade;

class redis extends facade
{
    
    public static function getFacadeAccessor()
    {
        return 'redis';
    }
}