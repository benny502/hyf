<?php
namespace hyf\facade;

use hyf\facade\core\facade;

class mysql extends facade
{
    
    public static function getFacadeAccessor()
    {
        return 'mysql';
    }
}