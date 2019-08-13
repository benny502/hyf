<?php
namespace hyf\facade;

use hyf\facade\core\facade;

class middleware extends facade
{

    public static function getFacadeAccessor()
    {
        return 'middleware';
    }
}