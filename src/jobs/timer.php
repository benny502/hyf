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



