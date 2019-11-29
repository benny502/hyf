<?php
namespace hyf\frame;

use hyf\component\middleware\middleware;
use hyf\facade\output;
use hyf\component\route\routerHandle;
use hyf\component\route\routerNormal;
use hyf\component\route\routerGroup;

class http
{

    public static function Handle()
    {
        try {
            // 获取路由模式
            $mode = !empty(app_config()['route']['mode']) ? app_config()['route']['mode'] : 'normal';
            // 获取自定义errorHook
            if (class_exists("\\application\\" . app_name() . "\\exception\\error")) {
                DI('errorHook', function () {
                    $errorHook = "\\application\\" . app_name() . "\\exception\\error";
                    return new $errorHook();
                });
            }

            // 处理路由
            switch ($mode) {
                case 'handle':
                    if(method_exists("\\application\\" . app_name() . "\\route\\router", "_before")) {
                        call_user_func_array([
                            "\\application\\" . app_name() . "\\route\\router",
                            "_before"
                        ],[]);
                    }
                    $result = routerHandle::dispatch();
                    if(method_exists("\\application\\" . app_name() . "\\route\\router", "_after")) {
                        call_user_func_array([
                            "\\application\\" . app_name() . "\\route\\router",
                            "_after"
                        ],[]);
                    }
                    break;
                case 'normal':
                case 'group':
                    self::routerParse($mode);
                    // 执行前置中间件
                    middleware::before();
                    $result = self::appRun();
                    break;
                default:
                    throw new \Exception("路由模式错误!");
            }

        } catch (\Exception $e) {
            if (method_exists(DI("errorHook"), 'exceptionHook')) {
                $result = DI("errorHook")->exceptionHook($e);
            } else {
                $result = output::error($e->getMessage());
            }
        } catch (\Error $e) {
            if (method_exists(DI("errorHook"), 'errorHook')) {
                $result = DI("errorHook")->errorHook($e);
            } else {
                $result = output::error($e->getMessage());
            }
        } finally {
            // response end
            response()->end($result);
            if ($mode != 'handle') {
                // 执行后置中间件
                middleware::after();
            }
        }
    }

    public static function routerParse($mode = 'normal')
    {
        // 自定义解析过程
        $ret = [];
        $routerHelper = '\\application\\' . app_name() . '\\route\\dispatch';
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

        if (!empty(\Hyf::$group)) {
            $current_controller_class = "\\application\\" . app_name() . "\\controller\\" . \Hyf::$group . '\\' . \Hyf::$controller;
        } else {
            $current_controller_class = "\\application\\" . app_name() . "\\controller\\" . \Hyf::$controller;
        }
        $current_action = \Hyf::$action;

        if (\class_exists($current_controller_class)) {
            $controller_obj = new $current_controller_class();
            if (\method_exists($controller_obj, $current_action)) {
                $result = $controller_obj->$current_action();
            } else {
                throw new \Exception("接口地址不存在!");
            }
        } else {
            throw new \Exception("接口地址不存在!");
        }

        return $result;
    }
}
