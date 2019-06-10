<?php
namespace hyf\container\core;

class container
{

    public static $instance;

    /**
     * 获取当前容器的实例（单例）
     *
     * @access public
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            static::$instance = new \Pimple\Container();
        }
        return self::$instance;
    }
    
}
