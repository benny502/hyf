<?php
namespace hyf\facade;

use hyf\facade\core\facade;

class table extends facade
{
    
    public static function getFacadeAccessor()
    {
        return 'table';
    }
}