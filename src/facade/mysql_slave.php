<?php
namespace hyf\facade;

use hyf\facade\core\facade;

class mysql_slave extends facade
{
    
    public static function getFacadeAccessor()
    {
        return 'mysql_slave';
    }
}