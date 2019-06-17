<?php
/**
 * Timer
 * process定时器：
 *  将会创建对应定时器数量的子进程来处理定时任务，定时器数量与子进程关系为一一对应。
 *  
 *  @author
 */
namespace hyf\jobs;

class timer
{

    public static function run($server, $process_name)
    {
        $timer_process_map = [];
        // process
        foreach (glob(\Hyf::$dir . 'application/' . app_name() . '/timer/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push($timer_process_map, $class);
        }
        foreach (glob(\Hyf::$dir . 'application/' . app_name() . '/timer/*/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(\Hyf::$dir, '', $start_file)));
            array_push($timer_process_map, $class);
        }
        if (!empty($timer_process_map)) {
            foreach ($timer_process_map as $timer) {
                // 创建子进程处理定时器
                $process = new \Swoole\Process(function ($process) use ($process_name, $timer) {
                    $timer_object = new $timer();
                    swoole_set_process_name($process_name . ' [timerName:' . $timer_object->name . ']');
                    //\Swoole\Timer::tick($timer_object->loop_time, function () use ($timer_object) {
                    //    $timer_object->run();
                    //});
                    
                    $last_run_time_process = time();
                    
                    \Swoole\Timer::tick(1000, function () use ($timer_object, &$last_run_time_process) {
                        $current_time = time();
                        $current_second = intval(date("s", $current_time));
                        // 解析crontab格式时间
                        $cronJob = \hyf\component\crond\parseCron::Run($timer_object->loop_time, $current_time, $last_run_time_process);
                        if ($cronJob !== false && in_array($current_second, $cronJob)) {
                            $last_run_time_process = $current_time;
                            $timer_object->run();
                        }
                    });
                });
                $server->addProcess($process);
            }
        }
    }
}



