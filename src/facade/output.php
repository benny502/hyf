<?php
namespace hyf\facade;

use hyf\facade\core\facade;

class output extends facade
{
    
    public static function getFacadeAccessor()
    {
        return 'output';
    }
}