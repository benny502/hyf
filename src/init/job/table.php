<?php
/**
 * table 初始化
 *
 * @author
 */
namespace hyf\init\job;

class table
{
    public static function run()
    {
        DI(md5('table'));
    }
}
