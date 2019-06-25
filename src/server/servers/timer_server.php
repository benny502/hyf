<?php
/**
 * timer server
 *
 * @author
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
        if (file_exists(app_dir() . 'conf/app.ini')) {
            \Hyf::$app_config = parse_ini_file(app_dir() . 'conf/app.ini', true) ?: [];
        } else {
            \Hyf::$app_config = [];
        }
        
        // default bind container
        \hyf\container\binds::Run('timer');
        
        // init jobs
        \hyf\init\init::Run('timer');
    }
}
