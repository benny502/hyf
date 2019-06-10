<?php
/**
 * Timer
 * master定时器：
 * 	将读取的定时器给master上全部安装一遍
 * worker定时器、task定时器：
 * 	将读取的worker或task定时器，分别给每个worker或task安装；如读取的定时器数量超过worker或task定时器
 * 数量，则会从首个worker或task定时器开始重新安装，直到安装完毕为止；如读取的定时器数量小于worker或task
 * 定时器数量，则将读取的定时器安装完毕就结束安装流程。
 * process定时器：
 *  将会创建对应定时器数量的子进程来处理定时任务，定时器数量与子进程关系为一一对应。
 *  
 *  @author
 */
namespace hyf\jobs;

class timer
{

    public static $timer_map = [];

    public static $timer_master_map = [];

    public static $timer_process_map = [];

    public static function map_timer($config)
    {
        // master
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/timer/master/*/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push(self::$timer_master_map, $class);
        }
        
        // process
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/timer/process/*/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push(self::$timer_process_map, $class);
        }
        
        // worker
        $worker_list = [];
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/timer/worker/*/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push($worker_list, $class);
        }
        // task
        $task_list = [];
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/timer/task/*/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push($task_list, $class);
        }
        // worker map
        $worker_map = [];
        if (!empty($worker_list)) {
            for ($i = 0, $j = 0; $j < count($worker_list); $i++, $j++) {
                if ($i % $config['server_set']['worker_num'] == 0) {
                    $i = 0;
                }
                $worker_map["process_{$i}"][] = $worker_list[$j];
            }
        }
        // task map
        $task_map = [];
        if (!empty($task_list)) {
            for ($i = 0, $j = 0; $j < count($task_list); $i++, $j++) {
                if ($i % $config['server_set']['task_worker_num'] == 0) {
                    $i = 0;
                }
                $t = $i + $config['server_set']['worker_num'];
                $task_map["process_{$t}"][] = $task_list[$j];
            }
        }
        self::$timer_map = array_merge($worker_map, $task_map);
    }

    public static function run_timer($server)
    {
        if (isset(self::$timer_map["process_{$server->worker_id}"])) {
            foreach (self::$timer_map["process_{$server->worker_id}"] as $timer) {
                $timer_object = new $timer();
                $server->tick($timer_object->loop_time, function () use ($timer_object) {
                    $timer_object->run();
                });
                // echo "process: " . $server->worker_id . "安装 {$timer} 定时器\n";
            }
        }
    }

    public static function run_master_timer($server)
    {
        foreach (self::$timer_master_map as $timer) {
            $timer_object = new $timer();
            $server->tick($timer_object->loop_time, function () use ($timer_object) {
                $timer_object->run();
            });
            // echo "process: " . $server->worker_id . "安装 {$timer} 定时器\n";
        }
    }

    public static function run_process_timer($server, $process_name)
    {
        if (!empty(self::$timer_process_map)) {
            foreach (self::$timer_process_map as $timer) {
                // 创建子进程处理定时器
                $process = new \Swoole\Process(function ($process) use ($process_name, $timer) {
                    swoole_set_process_name($process_name . "-timer");
                    $timer_object = new $timer();
                    \Swoole\Timer::tick($timer_object->loop_time, function () use ($timer_object) {
                        $timer_object->run();
                    });
                });
                $server->addProcess($process);
            }
        }
    }
}



