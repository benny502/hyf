<?php
/**
 * 初始化事件
 *
 * @author
 */
namespace hyf\init\job;

class start
{

    public static function run()
    {
        $master = [];
        foreach (glob(app_dir() . 'init/*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(root_path(), '', $start_file)));
            array_push($master, $class);
        }
        foreach (glob(app_dir() . 'init/*/*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(root_path(), '', $start_file)));
            array_push($master, $class);
        }
        if (!empty($master)) {
            foreach ($master as $worker) {
                $object = new $worker();
                $object->run();
            }
        }
    }
}
