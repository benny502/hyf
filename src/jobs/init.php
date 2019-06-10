<?php
/**
 * 框架初始化
 * 支持master、worker和task进程初始化脚本
 *
 * @author Makle <zhang.tao@hylinkad.com>
 */
namespace hyf\jobs;

class init
{

    public static $master = [];

    public static $worker = [];

    public static $task = [];

    public static function map_init($config)
    {
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/init/master/*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push(self::$master, $class);
        }
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/init/worker/*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push(self::$worker, $class);
        }
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/init/task/*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push(self::$task, $class);
        }
    }

    public static function run($workers, $server)
    {
        if (!empty($workers)) {
            foreach ($workers as $worker) {
                $object = new $worker();
                $object->run();
            }
        }
    }

    public static function run_master($server)
    {
        self::run(self::$master, $server);
    }

    public static function run_worker($server)
    {
        self::run(self::$worker, $server);
    }

    public static function run_task($server)
    {
        self::run(self::$task, $server);
    }
}