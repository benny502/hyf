<?php
/**
 * Class Hyf
 *
 * @author
 */
class Hyf
{

    /**
     * 版本号
     *
     * @var string
     */
    public static $version = "2.1.1";
    
    /**
     * 系统主路径
     *
     * @var string
     */
    public static $dir;
    
    /**
     * 全局配置文件
     *
     * @var array
     */
    public static $config;

    /**
     * 应用名称
     *
     * @var string
     */
    public static $app_name;

    /**
     * server对象
     *
     * @var object
     */
    public static $server;

    /**
     * 请求request对象
     *
     * @var object
     */
    public static $request;

    /**
     * 请求response对象
     *
     * @var object
     */
    public static $response;

    /**
     * 请求group名
     *
     * @var string
     */
    public static $group;

    /**
     * 请求controller名
     *
     * @var string
     */
    public static $controller;
    
    /**
     * 请求action名
     *
     * @var string
     */
    public static $action;
    
    /**
     * 应用配置文件
     *
     * @var array
     */
    public static $app_config;

    /**
     * 启动脚本
     */
    public static function Run($type = 'http')
    {
        \hyf\server\start::run($type);
    }
}