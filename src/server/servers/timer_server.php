<?php
/**
 * timer server
 *
 * @author Makle <zhang.tao@hylinkad.com>
 */
namespace hyf\server\servers;

class timer_server
{

    public static function run($config)
    {
        // daemonize set
        if ($config['server_set']['daemonize']) {
            \Swoole\Process::daemon(true, false);
        }
        // master process name set
        swoole_set_process_name($config['process_name']['master']);
        
        // 应用配置文件
        if(file_exists(\Hyf::$dir . 'application/' . $config['app_name'] . '/conf/app.ini')) {
            \Hyf::$app_config = parse_ini_file(\Hyf::$dir . 'application/' . $config['app_name'] . '/conf/app.ini', true) ?: [];
        } else {
            \Hyf::$app_config = [];
        }
        
        
        // memory
        \hyf\jobs\memory::init($config['app_name']);
        
        // init set
        self::init($config['app_name']);
        
        // timer jobs
        $timer_array = self::timer($config);
        
        if (! empty($timer_array)) {
            
            $workerNum = count($timer_array);
            $pool = new \Swoole\Process\Pool($workerNum);
            
            $pool->on("WorkerStart", function ($pool, $worker_id) use ($config, $timer_array) {
                $process_name = str_replace('{id}', $worker_id, $config['process_name']['worker']);
                swoole_set_process_name($process_name);
                
                $timer_object = new $timer_array[$worker_id]();
                //echo "Worker: {$worker_id} is started\n";
                \Swoole\Timer::tick($timer_object->loop_time, function () use ($timer_object) {
                    $timer_object->run();
                });
            });
            
            $pool->on("WorkerStop", function ($pool, $worker_id) {
                //echo "Worker: {$worker_id} is stopped\n";
            });
            
            $pool->start();
        }
    }

    private static function init($app_name)
    {
        $init_array = [];
        foreach (glob(\Hyf::$dir . 'application/' . $app_name . '/init/*.php') as $initFile) {
            array_push($init_array, "application\\{$app_name}\\init\\" . basename($initFile, '.php'));
        }
        if (! empty($init_array)) {
            foreach ($init_array as $init) {
                call_user_func_array(array(
                    new $init(),
                    'run'
                ), array());
            }
        }
    }

    private static function timer($config)
    {
        $timer_array = [];
        foreach (glob(\Hyf::$dir . 'application/' . $config['app_name'] . '/timer/*.php') as $timerFile) {
            array_push($timer_array, "application\\{$config['app_name']}\\timer\\" . basename($timerFile, '.php'));
        }
        return $timer_array;
    }
}
