<?php
/**
 * 启动脚本
 *
 * @author
 */
namespace hyf\server;

class start
{

    private static $daemonize = false;

    private static $master_pid = '';

    private static function parseCli()
    {
        global $argv;
        
        if (!isset($argv[1]) && !isset($argv[2])) {
            exit("使用方法 php {http|timer} app_name {start|stop|killall|reload|reload_task} (-d)\n");
        }
        
        //
        $program = explode("/", $argv[0]);
        $argv[0] = array_pop($program);
        unset($program);
        
        if (!in_array($argv[0], [
            'http', 
            'timer'
        ])) {
            exit("参数使用不正确\n");
        }
        
        $conf_path = \Hyf::$dir . 'application' . '/' . $argv[1] . '/conf/server.php';
        
        if (file_exists($conf_path)) {
            $server_config = include($conf_path);
        } else {
            if ($argv[0] == 'http') {
                exit("服务器参数配置不正确\n");
            }
        }
        
        $server_config['app_name'] = $argv[1];
        
        if (!in_array($argv[2], [
            'start', 
            'stop', 
            'killall',
            'reload',
            'reload_task'
        ])) {
            exit("参数使用不正确\n");
        }
        
        if (isset($argv[3]) && $argv[3] == '-d') {
            self::$daemonize = true;
        }
        
        return [
            $argv[2], 
            $server_config
        ];
    }

    private static function get_master_pid($master_name)
    {
        self::$master_pid = trim(shell_exec("pidof {$master_name}"));
    }

    public static function run($server_type)
    {
        try {
            list($action, $server_config) = self::parseCli();
            $server_config['service_type'] = $server_type;
            if ($server_config['service_type'] == 'timer') {
                $server_config['process_name'] = [
                    'base' => 'hy_' . $server_config['app_name'],
                    'master' => 'hy_' . $server_config['app_name'] . '_master', 
                    'worker' => 'hy_' . $server_config['app_name'] . '_worker[{id}]'
                ];
            } else {
                $server_config['process_name'] = [
                    'base' => 'hy_' . $server_config['app_name'],
                    'master' => 'hy_' . $server_config['app_name'] . '_master', 
                    'manager' => 'hy_' . $server_config['app_name'] . '_manager', 
                    'worker' => 'hy_' . $server_config['app_name'] . '_worker[{id}]', 
                    'task' => 'hy_' . $server_config['app_name'] . '_task[{id}]'
                ];
                // 抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
                $server_config['server_set']['dispatch_mode'] = 3;
                // 设置task async，可以使用协程等
                $server_config['server_set']['task_async'] = true;
            }
            
            self::get_master_pid($server_config['process_name']['master']);

            switch ($action) {
                case 'start':
                    self::startService($server_config);
                    break;
                case 'stop':
                    self::stopService();
                    break;
                case 'killall':
                    self::killall($server_config['app_name']);
                    break;
                case 'reload':
                    self::reload();
                    break;
                case 'reload_task':
                    self::reload_task();
                    break;
            }
        } catch (\Exception $e) {
            exit("Error: \n File: {$e->getFile()} ,Line: {$e->getLine()}, Message: {$e->getMessage()}\n");
        } catch (\Error $e) {
            exit("Error: \n File: {$e->getFile()} ,Line: {$e->getLine()}, Message: {$e->getMessage()}\n");
        }
    }

    private static function startService(array $server_config)
    {
        if (empty(self::$master_pid)) {
            echo "\n";
            echo "\033[0;42;37m***************************************************************\033[0m\n";
            echo "\033[0;42;37m*                                                             *\033[0m\n";
            echo "\033[0;42;37m*           __             _____             __               *\033[0m\n";
            echo "\033[0;42;37m*          / /            / ___/            / /               *\033[0m\n";
            echo "\033[0;42;37m*         / /_  __   __  / /__     ____    / /_    ____       *\033[0m\n";
            echo "\033[0;42;37m*        / __ \ \ \ / / / .__/    / __ \  / __ \  / __ \      *\033[0m\n";
            echo "\033[0;42;37m*       / / / /  \_/ / / /       / /_/ / / / / / / /_/ /      *\033[0m\n";
            echo "\033[0;42;37m*      /_/ /_/    / / /_/       / .___/ /_/ /_/ / .___/       *\033[0m\n";
            echo "\033[0;42;37m*               _/_/           /_/             /_/            *\033[0m\n";
            echo "\033[0;42;37m*                                                             *\033[0m\n";
            echo "\033[0;42;37m*                                                             *\033[0m\n";
            echo "\033[0;42;37m***************************************************************\033[0m\n";
            echo "\n服务已经正常启动...";
            echo "\nphp版本: \033[32m" . PHP_VERSION . "\033[0m";
            echo "\nswoole版本: \033[32m" . swoole_version() . "\033[0m";
            echo "\nhyf版本: \033[32m" . version() . "\033[0m\n\n";
            
            // set daemonize
            $server_config['server_set']['daemonize'] = self::$daemonize;
            if (!self::$daemonize) {
                echo "\n\n\033[0;33m***************************调试模式****************************\033[0m\n\n";
            }
            // 全局配置
            \Hyf::$config = include(\Hyf::$dir . 'conf/base.php');
            \Hyf::$app_name = $server_config['app_name'];

            // set log file
            $server_config['server_set']['log_file'] = log_path() . $server_config['app_name'] . '_server.log';

            \Hyf::$server_config = $server_config;
            
            // 一键php原生语句协程化
            if(isset($server_config['enableCoroutine']) && $server_config['enableCoroutine'] == 1) {
                \Swoole\Runtime::enableCoroutine(true);
            }
            
            // start server
            call_user_func_array(array(
                '\\hyf\\server\\servers\\' . $server_config['service_type'] . '_server', 
                'run'
            ), array(
                $server_config
            ));
        } else {
            throw new \Exception("服务正在运行，请勿重复启动\n");
        }
    }

    private static function stopService()
    {
        if (empty(self::$master_pid)) {
            throw new \Exception("服务尚未运行\n");
        } else {
            \system("kill -9 -" . self::$master_pid);
        }
    }

    private static function killall($app_name)
    {
        \system('ps -ef|grep hy_' . $app_name . '|grep -v grep|awk \'{print "kill -9 " $2}\' |sh');
    }

    /**
     * 平滑重启所有worker，仅针对业务代码所做的修改起效，对全局的定时器、初始化脚本的修改不起作用
     * @throws \Exception
     */
    private static function reload()
    {
        if (empty(self::$master_pid)) {
            throw new \Exception("服务尚未运行\n");
        } else {
            \system("kill -USR1 " . self::$master_pid);
            echo "worker将逐步进行重启\n";
        }
    }

    /**
     * 平滑重启所有task，仅针对业务代码所做的修改起效，对全局的定时器、初始化脚本的修改不起作用
     * @throws \Exception
     */
    private static function reload_task()
    {
        if (empty(self::$master_pid)) {
            throw new \Exception("服务尚未运行\n");
        } else {
            \system("kill -USR2 " . self::$master_pid);
            echo "task将逐步进行重启\n";
        }
    }
}