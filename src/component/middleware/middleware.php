<?php
namespace hyf\component\middleware;

class middleware
{

    public static function before()
    {
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
    }

    public static function after()
    {
        // 执行后置中间件
        $class_init = "\\application\\" . app_name() . "\\middleware\\after";
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
    }
}