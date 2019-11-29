<?php
/**
 * http server
 *
 * @author
 */
namespace hyf\server\servers;

class http_server
{

    public static function run($config)
    {
        // create server
        $server = new \Swoole\Http\Server($config['host'], $config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $server->set($config['server_set']);
        
        // 应用配置文件
        if (file_exists(app_dir() . 'conf/app.php')) {
            \Hyf::$app_config = include(app_dir() . 'conf/app.php') ?: [];
        } else {
            \Hyf::$app_config = [];
        }
        
        // 设置server对象
        \Hyf::$server = $server;
        
        // default bind container
        \hyf\container\binds::Run();
        
        // init jobs
        \hyf\init\init::Run();
        
        $server->on('start', function ($server) use ($config) {
            swoole_set_process_name($config['process_name']['master']);
        });
        
        $server->on('managerStart', function ($server) use ($config) {
            swoole_set_process_name($config['process_name']['manager']);
        });
        
        $server->on('workerStart', function ($server, $worker_id) use ($config) {
            if ($worker_id >= $server->setting['worker_num']) {
                $process_name = str_replace('{id}', $worker_id, $config['process_name']['task']);
                swoole_set_process_name($process_name);
            } else {
                $process_name = str_replace('{id}', $worker_id, $config['process_name']['worker']);
                swoole_set_process_name($process_name);
            }
        });
        
        
        // 注册handle模式路由
        if(!empty(app_config()['route']['mode']) && app_config()['route']['mode'] == 'handle') {
            call_user_func_array([
                "\\application\\" . app_name() . "\\route\\router",
                "Run"
            ],[
                \hyf\component\route\routerHandle::class
            ]);
        }
        
        $server->on('request', function ($request, $response) {
            
            // 将request和response设置为全局对象
            \Hyf::$request = $request;
            \Hyf::$response = $response;
            
            // 处理非业务请求
            if (request()->server['path_info'] == '/favicon.ico' || request()->server['request_uri'] == '/favicon.ico') {
                return response()->end();
            }
            
            // 响应头
            response()->header("Content-Type", "application/json; charset=utf-8");
            
            // http handle
            \hyf\frame\http::Handle();
            
        });
        
        $server->on('task', function (\swoole_server $server, $task_id, $from_id, $data) {
            $ret = call_user_func_array([new $data["class"], $data["method"]], [$data["data"]]);
            return is_null($ret) ? '' : $ret;
        });
        
        $server->start();
    }
}
