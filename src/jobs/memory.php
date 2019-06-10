<?php
/**
 * 内存表
 *
 * @author Makle <zhang.tao@hylinkad.com>
 */
namespace hyf\jobs;

use hyf\container\core\container;

class memory
{

    public static $table_map = [];

    public static function map_table($app_name)
    {
        // list
        foreach (glob(\Hyf::$dir . 'application/' . $app_name . '/conf/table/*.php') as $table) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $table)));
            array_push(self::$table_map, $class);
        }
    }

    // table init
    public static function table_init($app_name)
    {
        // memory table
        self::map_table($app_name);
        if (!empty(self::$table_map)) {
            $memory_table = new \hyf\component\stdClass\std();
            foreach (self::$table_map as $table_class) {
                $memory_table->{$table_class::$name} = new \hyf\component\memory\table($table_class::$table);
            }
            container::getInstance()["table"] = function () use ($memory_table) {
                return $memory_table;
            };
        }
    }

    public static function init($app_name)
    {
        self::table_init($app_name);
    }
}