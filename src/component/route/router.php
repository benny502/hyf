<?php
namespace hyf\component\route;

use hyf\component\exception\myException;

class router
{

    public static function run()
    {
        if (isset(\Hyf::$request->server['path_info'])) {
            \Hyf::$request->server['path_info'] = trim(\Hyf::$request->server['path_info'], "/");
        }
        if (\Hyf::$request->server['path_info'] == '') {
            \Hyf::$request->server['path_info'] = 'home/index';
        }
        
        if (substr_count(\Hyf::$request->server['path_info'], '/') > 1) {
            throw new myException("您访问的URL有误，请重新输入URL。");
        }
        
        $_regx = \Hyf::$request->server['path_info'];
        $_pathinfo = false === strpos($_regx, '/') ? $_regx . '/index' : $_regx;
        list($controller, $action) = explode('/', $_pathinfo);
        // 处理controller多级目录
        $controller = str_replace("-", "\\", $controller);
        
        return [
            $controller, 
            $action
        ];
    }
}
