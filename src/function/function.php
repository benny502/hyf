<?php

/**
 * 应用内跨模块/文件夹调用 
 * 第一个参数为 类路径
 * 后续可变参数列表陆续为 对应类的构造参数列表，如果没有构造函数，无需传入该参数
 * @param $class
 * @return mixed
 */
function hyf(...$param)
{
    $class = str_replace('/', '_', array_shift($param));
    return \hyf\component\call\call::$class($param);
}

/**
 * 应用内跨模块调用模型 默认model目录，使用方法惨开 hyf函数
 *
 * @param
 *            $model
 * @return mixed
 */
function model(...$param)
{
    $class = 'model_' . str_replace('/', '_', array_shift($param));
    return \hyf\component\call\call::$class($param);
}

/**
 * 应用内跨模块调用模型 默认helper目录，使用方法惨开 hyf函数
 *
 * @param
 *            $helper
 * @return mixed
 */
function helper(...$param)
{
    $class = 'helper_' . str_replace('/', '_', array_shift($param));
    return \hyf\component\call\call::$class($param);
}

/**
 *
 * @param string $config            
 * @return \hyf\component\db\mysql|mixed
 */
function mysql($config = 'mysql')
{
    if (!empty(Hyf::$config[$config])) {
        return call_user_func("\\hyf\\facade\\mysql::{$config}");
    }
    return NULL;
}

/**
 *
 * @param string $config            
 * @return \hyf\component\db\redis|mixed
 */
function redis($config = 'redis')
{
    if (!empty(Hyf::$config[$config])) {
        return call_user_func("\\hyf\\facade\\redis::{$config}");
    }
    return NULL;
}

/**
 *
 * @param string $config            
 * @return bool|mixed
 */
function table($config = '')
{
    if (!empty($config)) {
        return call_user_func("\\hyf\\component\\memory\\table\\table::{$config}");
    }
    return NULL;
}

/**
 *
 * @param string $config            
 * @return bool|mixed
 */
function queue($config = '')
{
    if (!empty($config)) {
        return call_user_func("\\hyf\\component\\memory\\queue\\queue::{$config}");
    }
    return NULL;
}

/**
 *
 * @param string $key            
 * @return array
 */
function config($key = '')
{
    if (!empty($key)) {
        return Hyf::$config[$key];
    }
    return Hyf::$config;
}

/**
 *
 * @param string $key            
 * @return array
 */
function app_config($key = '')
{
    if (!empty($key)) {
        return Hyf::$app_config[$key];
    }
    return Hyf::$app_config;
}

/**
 *
 * @param string $key
 * @return array
 */
function server_config($key = '')
{
    if (!empty($key)) {
        return Hyf::$server_config[$key];
    }
    return Hyf::$server_config;
}

/**
 *
 * @return string
 */
function app_name()
{
    return Hyf::$app_name;
}

/**
 *
 * @return string
 */
function app_dir()
{
    return root_path() . 'application' . '/' . app_name() . '/';
}

/**
 *
 * @return object
 */
function request()
{
    return Hyf::$request;
}

/**
 *
 * @return object
 */
function response()
{
    return Hyf::$response;
}

/**
 *
 * @return object
 */
function server()
{
    return Hyf::$server;
}

/**
 *
 * @return string
 */
function version()
{
    return Hyf::$version;
}

/**
 *
 * @return string
 */
function root_path()
{
    return Hyf::$dir;
}

/**
 * @param $key
 * @return string
 */
function json($key = '') {
    $arr = json_decode(request()->rawContent(), true);
    if (!$arr) {
        return '';
    }

    return !empty($key) ? (isset($arr[$key]) ? $arr[$key] : '') : $arr;
}

/**
 *
 * @return string
 */
function log_path()
{
    if (empty(Hyf::$config['log']['dir'])) {
        $path = '/tmp/log';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return '/tmp/log/';
    }
    
    return Hyf::$config['log']['dir'];
}

/**
 * 容器快速调用方法
 * exmple: 
 *  DI('user', function(){ ...}); 
 *  DI('name', 'abcdefg');
 */
function DI(...$params)
{
    if (count($params) == 1 && \hyf\container\core\container::getInstance()->offsetExists($params[0])) {
        return \hyf\container\core\container::getInstance()[$params[0]];
    } elseif (count($params) == 2) {
        \hyf\container\core\container::getInstance()->offsetUnset($params[0]);
        \hyf\container\core\container::getInstance()[$params[0]] = $params[1];
    } else {
        return '';
    }
}

/**
 * task 开始一个异步任务
 * $class_method: 处理异步任务的类和方法名，例如: async/test/md::tsd
 * $data: 传入异步任务的数据
 * $callback: 异步任务处理完成后执行的回调函数，该回调函数的参数$data为异步任务处理结束时返回的值，$task_id为处理该异步任务的task id
 * $dst_worker_id: 投递给那个异步task去处理，-1为空闲的任务
 */
function task($class_method, $data = '', $callback = '', $dst_worker_id = -1)
{
    $cm_arr = explode('::',$class_method);
    if(count($cm_arr) !=2 ){
        return false;
    }

    list($class, $method) = $cm_arr;
    $class = '\\application\\' . app_name() . '\\' . str_replace("/", "\\", $class);

    if(class_exists($class) && method_exists($class, $method)){
        if(!empty($callback) && is_callable($callback)){
            return server()->task(["class"=>$class, "method"=>$method, "data"=>$data, "finish"=>true], $dst_worker_id, function($server, $task_id, $data) use($callback){
                $callback($data, $task_id);
            });
        }
        return server()->task(["class"=>$class, "method"=>$method, "data"=>$data, "finish"=>false], $dst_worker_id);
    }
    return false;
}


