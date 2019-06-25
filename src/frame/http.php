<?php
namespace hyf\frame;

use hyf\component\route\routerHandler;
use hyf\component\route\routerNormal;
use hyf\component\route\routerGroup;
use hyf\component\exception\myException;

class http
{

    public static function Handler()
    {
        try {
            // 获取路由模式
            $mode = !empty(app_config()['route']['mode']) ? app_config()['route']['mode'] : 'normal';

            // 处理路由
            switch ($mode) {
                case 'handler':
                    $routerHandler = "\\application\\" . app_name() . "\\route\\handler";
                    $result = $routerHandler::Run(routerHandler::class);
                    response()->end($result);
                    break;
                case 'normal':
                case 'group':
                    self::routerParse($mode);
                    self::appRun();
                    break;
            }
        } catch (myException $e) {
            response()->end($e->show());
        }
    }

    public static function routerParse($mode = 'normal')
    {
        // 自定义解析过程
        $ret = [];
        $routerHelper = '\\application\\' . app_name() . '\\route\\router';
        if (\class_exists($routerHelper)) {
            $ret = $routerHelper::run();
        } else {
            if ($mode == 'normal') {
                $ret = routerNormal::run();
            } elseif ($mode == 'group') {
                $ret = routerGroup::run();
            }
        }

        if (count($ret) == 2) {
            array_unshift($ret, '');
        }

        list($group, $controller, $action) = $ret;

        \Hyf::$controller = $controller;
        \Hyf::$action = $action;
        \Hyf::$group = $group;
    }

    public static function appRun()
    {
        // 执行前置中间件
        $class_init = "\\application\\" . app_name() . "\\middleware\\before";
        if (\class_exists($class_init)) {
            $initialization = new \ReflectionClass($class_init);
            foreach ($initialization->getMethods() as $method) {
                // global 所有挨着执行
                if (strpos($method->name, 'global') !== false) {
                    $method->invoke($initialization->newInstance());
                } else {
                    if (!empty(\Hyf::$group)) {
                        $funName = 'router_' . \Hyf::$group . '_' . str_replace("\\", "_", \Hyf::$controller) . '_' . \Hyf::$action;
                    } else {
                        $funName = 'router_' . str_replace("\\", "_", \Hyf::$controller) . '_' . \Hyf::$action;
                    }
                    if ($method->name == $funName) {
                        $method->invoke($initialization->newInstance());
                    }
                }
            }
        }

        if (!empty(\Hyf::$group)) {
            $current_controller_class = "\\application\\" . app_name() . "\\controller\\" . \Hyf::$group . '\\' . \Hyf::$controller;
        } else {
            $current_controller_class = "\\application\\" . app_name() . "\\controller\\" . \Hyf::$controller;
        }
        $current_action = \Hyf::$action;

        if (\class_exists($current_controller_class)) {
            $controller_obj = new $current_controller_class();
            if (\method_exists($controller_obj, $current_action)) {
                response()->end($controller_obj->$current_action());
            } else {
                throw new myException("接口地址不存在!");
            }
        } else {
            throw new myException("接口地址不存在!");
        }
    }
}
