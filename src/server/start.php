<?php
/**
 * 启动脚本
 *
 * @author Makle <zhang.tao@hylinkad.com>
 */
namespace hyf\server;

class start
{
    
    private static $daemonize = false;
    
    private static $master_pid = '';
    
    private static function parseCli()
    {
        global $argv;
        
        if (! isset($argv[1])) {
            exit("使用方法 php yourfile.php {start|stop|killall} (-d)\n");
        }
        
        if (! in_array($argv[1], [
            'start',
            'stop',
            'killall'
        ])) {
            exit("参数使用不正确\n");
        }
        
        if (isset($argv[2]) && $argv[2] == '-d') {
            self::$daemonize = true;
        }
        
        return $argv[1];
    }
    
    private static function get_master_pid($master_name)
    {
        self::$master_pid = trim(shell_exec("pidof {$master_name}"));
    }
    
    public static function run($server_config)
    {
        try {
            $action = self::parseCli();
            if ($server_config['service_type'] == 'timer') {
                $server_config['process_name'] = [
                    'master' => 'hy_' . $server_config['app_name'] . '_master_worker',
                	'worker' => 'hy_' . $server_config['app_name'] . '_worker[workerID:{id}]',
                ];
            } else {
                $server_config['process_name'] = [
                    'master' => 'hy_' . $server_config['app_name'] . '_master_worker',
                    'manager' => 'hy_' . $server_config['app_name'] . '_manager_worker',
                    'worker' => 'hy_' . $server_config['app_name'] . '_worker[workerID:{id}]',
                    'task' => 'hy_' . $server_config['app_name'] . '_task_worker[workerID:{id}]'
                ];
                // 抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
                $server_config['server_set']['dispatch_mode'] = 3;
                // 设置task async，可以使用协程等
                $server_config['server_set']['task_async'] = true;
                // set log file
                $server_config['server_set']['log_file'] = \Hyf::$dir . 'log/' . $server_config['app_name'] . '_server.log';
            }
            
            self::get_master_pid($server_config['process_name']['master']);
            
            if ($action == 'start') {
                if (empty(self::$master_pid)) {
                    echo <<<EOL

***************************************************************
*           __             _____             __               *
*          / /            / ___/            / /               *
*         / /_  __   __  / /__     ____    / /_    ____       *
*        / __ \ \ \ / / / .__/    / __ \  / __ \  / __ \      *
*       / / / /  \_/ / / /       / /_/ / / / / / / /_/ /      *
*      /_/ /_/    / / /_/       / .___/ /_/ /_/ / .___/       *
*               _/_/           /_/             /_/            *
*                                                             *
*                                                             *
***************************************************************

EOL;
                    echo "\n服务已经启动...\nPHP版本: " . PHP_VERSION . "\nSWOOLE版本: " . swoole_version() . PHP_EOL;
                    // set daemonize
                    $server_config['server_set']['daemonize'] = self::$daemonize;
                    if (! self::$daemonize) {
                        echo <<<EOL


***************************调试模式****************************


EOL;
                    }
                    // 全局配置
                    \Hyf::$config = parse_ini_file(\Hyf::$dir . 'conf/base.ini', true);
                    // start server
                    self::startService($server_config);
                } else {
                    throw new \Exception("服务正在运行，请勿重复启动\n");
                }
            } elseif($action == 'stop') {
                if (empty(self::$master_pid)) {
                    throw new \Exception("服务尚未运行\n");
                } else {
                    self::stopService();
                }
            }else{
                self::killall($server_config['app_name']);
            }
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }
    
    private static function startService(array $server_config)
    {
        call_user_func_array(array(
            '\\hyf\\server\\servers\\' . $server_config['service_type'] . '_server',
            'run'
        ), array(
            $server_config
        ));
    }
    
    private static function stopService()
    {
        \system("kill -9 -" . self::$master_pid);
    }
    
    private static function killall($app_name)
    {
        \system('ps -ef|grep hy_'.$app_name.'|grep -v grep|awk \'{print "kill -9 " $2}\' |sh');
    }
}