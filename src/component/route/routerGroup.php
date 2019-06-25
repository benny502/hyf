<?php
namespace hyf\component\route;

use hyf\component\exception\myException;

class routerGroup
{

    public static function run()
    {
        if (isset(\Hyf::$request->server['path_info'])) {
            \Hyf::$request->server['path_info'] = trim(\Hyf::$request->server['path_info'], "/");
        }

        if (\Hyf::$request->server['path_info'] == '') {
            \Hyf::$request->server['path_info'] = 'home/home/index';
        }
        if (substr_count(\Hyf::$request->server['path_info'], '/') > 2) {
            throw new myException("您访问的URL有误，请重新输入URL。");
        }
        $_regx = \Hyf::$request->server['path_info'];

        if (false === strpos($_regx, '/')) {
            $_pathinfo =  $_regx . '/home/index';
        } elseif (substr_count($_regx, '/') == 1) {
            $_pathinfo =  $_regx . '/index';
        } else {
            $_pathinfo =  $_regx;
        }

        list($group, $controller, $action) = explode('/', $_pathinfo);

        // 处理controller多级目录
        $controller = str_replace("-", "\\", $controller);

        return [
            $group,
            $controller,
            $action
        ];
    }
}
