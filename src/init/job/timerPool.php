<?php
namespace hyf\init\job;

class timerPool
{

    public static function run()
    {
        if (!is_dir(app_dir())) {
            exit("应用名称错误！\n\n");
        }
        
        $timer_array = [];
        // process
        foreach (glob(app_dir() . 'timer/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(root_path(), '', $start_file)));
            array_push($timer_array, $class);
        }
        foreach (glob(app_dir() . 'timer/*/timer_*.php') as $start_file) {
            $class = str_replace('/', '\\', str_replace('.php', '', str_replace(root_path(), '', $start_file)));
            array_push($timer_array, $class);
        }
        
        // 获取主进程ID
        $pid = posix_getpid();
        
        $workerNum = count($timer_array);
        $pool = new \Swoole\Process\Pool($workerNum);
        
        $pool->on("WorkerStart", function ($pool, $worker_id) use ($timer_array, $pid) {
            
            // 如果不存在相关定时器则报错及退出进程执行
            if (!class_exists($timer_array[$worker_id])) {
                echo "Class [{$timer_array[$worker_id]}] not exists!" . PHP_EOL;
                // \Swoole\Process::kill("-{$pid}", SIGKILL);
                \system("kill -9 -{$pid}");
            }
            
            // job实例
            $timer_object = new $timer_array[$worker_id]();
            
            // 构造并设置进程名称
            $process_name = str_replace('{id}', $worker_id, server_config('process_name')['base'] . '_timer[{id}][' . $timer_object->name . ']');
            swoole_set_process_name($process_name);
            // echo "Worker: {$worker_id} is started\n";
            
            $last_run_time = time();
            
            // 安装定时器
            \Swoole\Timer::tick(1000, function () use ($timer_object, &$last_run_time) {
                $current_time = time();
                $current_second = intval(date("s", $current_time));
                // 解析crontab格式时间
                $cronJob = \hyf\component\crond\parseCron::Run($timer_object->loop_time, $current_time, $last_run_time);
                if ($cronJob !== false && in_array($current_second, $cronJob)) {
                    $last_run_time = $current_time;
                    $timer_object->run();
                }
            });
        });
        $pool->on("WorkerStop", function ($pool, $worker_id) {
            // echo "Worker: {$worker_id} is stopped\n";
        });
        
        $pool->start();
    }
}
