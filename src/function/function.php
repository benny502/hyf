<?php
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