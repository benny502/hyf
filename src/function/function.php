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
 * @param $model
 * @return mixed
 */
function model(...$param)
{
    $class = 'model_' . str_replace('/', '_', array_shift($param));
    return \hyf\component\call\call::$class($param);
}

/**
 * 应用内跨模块调用模型 默认helper目录，使用方法惨开 hyf函数
 * @param $helper
 * @return mixed
 */
function helper(...$param)
{
    $class = 'helper_' . str_replace('/', '_', array_shift($param));
    return \hyf\component\call\call::$class($param);
}

/**
 * @param string $config
 * @return \hyf\component\db\mysql|mixed
 * @throws \hyf\component\exception\myException
 */
function mysql($config = 'mysql')
{
    static $reg = [];
    if (!empty($reg[$config])) {
        return $reg[$config];
    }

    if (empty(Hyf::$config[$config])) {
        throw new \hyf\component\exception\myException("mysql配置错误!");
    }

    return $reg[$config] = new \hyf\component\db\mysql($config);
}

/**
 * @param string $config
 * @return \hyf\component\db\redis|mixed
 * @throws \hyf\component\exception\myException
 */
function redis($config = 'redis')
{
    static $reg = [];
    if (!empty($reg[$config])) {
        return $reg[$config];
    }

    if (empty(Hyf::$config[$config])) {
        throw new \hyf\component\exception\myException("redis配置错误!");
    }

    return $reg[$config] = new \hyf\component\db\redis($config);
}

/**
 * @param string $config
 * @return bool|mixed
 */
function table($config = '')
{
    if (!empty($config)) {
        return call_user_func_array(['\hyf\facade\table', $config], []);
    }

    return false;
}

/**
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

function app_name()
{
    return Hyf::$app_name;
}

/**
 * @return object
 */
function request()
{
    return Hyf::$request;
}

/**
 * @return object
 */
function response()
{
    return Hyf::$response;
}

/**
 * @return object
 */
function server()
{
    return Hyf::$server;
}

/**
 * @return string
 */
function version()
{
    return Hyf::$version;
}

/**
 * @return string
 */
function rootPath()
{
    return Hyf::$dir;
}

/**
 * @return string
 */
function logPath()
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
