<?php
/**
 * 框架初始化
 * 支持master、worker和task进程初始化脚本
 *
 * @author
 */
namespace hyf\jobs;

class init
{

    public static function run($server)
    {
        $master = [];
        foreach (glob(\Hyf::$dir . 'application/' . app_name() . '/init/*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push($master, $class);
        }
        foreach (glob(\Hyf::$dir . 'application/' . app_name() . '/init/*/*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
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