<?php
/**
 * table 初始化
 *
 * @author
 */
namespace hyf\init\job;

use hyf\component\memory\table\table;
use hyf\component\memory\table\core as table_core;
use hyf\component\memory\queue\queue;
use hyf\component\memory\queue\core as queue_core;

class memory
{
    public static function run()
    {
        // table
        $class_table = "\\application\\" . app_name() . "\\conf\\table";
        if (class_exists($class_table) && !empty($class_table::$column)) {
            foreach ($class_table::$column as $key => $value) {
                table::$table[$key] = new table_core($value);
            }
        }

        // queue
        $class_queue = "\\application\\" . app_name() . "\\conf\\queue";
        if (class_exists($class_queue) && !empty($class_queue::$keys)) {
            foreach ($class_queue::$keys as $key => $value) {
                queue::$queue[$key] = new queue_core($value);
            }
        }
    }
}
