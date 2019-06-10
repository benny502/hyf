<?php
/**
 * http server
 *
 * @author Makle <zhang.tao@hylinkad.com>
 */
namespace hyf\server\servers;

use hyf\component\exception\myException;

class http_server
{

    public static function run($config)
    {
        // create server
        $server = new \Swoole\Http\Server($config['host'], $config['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $server->set($config['server_set']);
        
        // 应用配置文件
        if(file_exists(\Hyf::$dir . 'application/' . $config['app_name'] . '/conf/app.ini')) {
            \Hyf::$app_config = parse_ini_file(\Hyf::$dir . 'application/' . $config['app_name'] . '/conf/app.ini', true) ?: [];
        } else {
            \Hyf::$app_config = [];
        }
        
        // 设置server对象
        \Hyf::$server = $server;
        
        // default bind container
        \hyf\container\binds::Run();
        
        // memory
        \hyf\jobs\memory::init($config['app_name']);
        
        // init
        \hyf\jobs\init::map_init($config);
        
        // timer
        \hyf\jobs\timer::map_timer($config);
        
        // run other listen-service
        \hyf\jobs\server::run($server, $config['app_name']);
        
        $server->on('start', function ($server) use ($config) {
            swoole_set_process_name($config['process_name']['master']);
            // run master init jobs
            \hyf\jobs\init::run_master($server);
            // run master timer jobs
            \hyf\jobs\timer::run_master_timer($server);
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
            
            // run init jobs
            if ($server->taskworker) {
                \hyf\jobs\init::run_task($server);
            } else {
                \hyf\jobs\init::run_worker($server);
            }
            // run timer jobs
            \hyf\jobs\timer::run_timer($server);
        });
        
        $server->on('request', function ($request, $response) use ($server, $config) {
            
            \Hyf::$request = $request;
            \Hyf::$response = $response;
            
            if (\Hyf::$request->server['path_info'] == '/favicon.ico' || \Hyf::$request->server['request_uri'] == '/favicon.ico') {
                return \Hyf::$response->end();
            }
            
            \Hyf::$response->header("Content-Type", "application/json; charset=utf-8");
            
            try {
                
                // router
                list($controller, $action) = \hyf\component\route\router::run();
                \Hyf::$controller = $controller;
                \Hyf::$action = $action;
                
                // 执行应用初始化方法
                $class_init = "\\application\\" . $config['app_name'] . "\\init\\app";
                if (\class_exists($class_init)) {
                    $initialization = new \ReflectionClass($class_init);
                    foreach ($initialization->getMethods() as $method) {
                        $method->invoke($initialization->newInstance());
                    }
                }
                
                $current_controller_class = "\\application\\" . $config['app_name'] . "\\controller\\" . \Hyf::$controller;
                $current_action = \Hyf::$action;
                
                if (\class_exists($current_controller_class)) {
                    $controller_obj = new $current_controller_class();
                    if (\method_exists($controller_obj, $current_action)) {
                        \Hyf::$response->end($controller_obj->$current_action());
                    } else {
                        throw new myException("接口地址不存在!");
                    }
                } else {
                    throw new myException("接口地址不存在!");
                }
            } catch (myException $e) {
                \Hyf::$response->end($e->show());
            }
        });
        
        $server->on('task', function ($server, $task_id, $from_id, $data) use($config) {
            call_user_func_array(array(
                "\\application\\" . $config['app_name'] . "\\async\\async", 
                'task'
            ), array(
                $server, 
                $task_id, 
                $from_id, 
                $data
            ));
        });
        
        $server->on('finish', function ($server, $task_id, $data) use($config) {
            call_user_func_array(array(
                "\\application\\" . $config['app_name'] . "\\async\\async", 
                'finish'
            ), array(
                $server, 
                $task_id, 
                $data
            ));
        });
        
        // set process timer
        \hyf\jobs\timer::run_process_timer($server, $config['process_name']['manager']);
        
        $server->start();
    }
}
