<?php
namespace hyf\container;

use hyf\container\core\container;
use hyf\component\io\output;

class binds
{

    // 默认bind列表(框架级)
    protected static $binds = [
//        'mysql' => [
//            'class' => mysql::class,
//            'config' => 'mysql'
//        ],
        'output' => output::class
    ];

    public static function Run()
    {
        foreach (self::$binds as $key => $value) {
            container::getInstance()[$key] = function () use ($value) {
                if (!is_array($value)) {
                    return new $value();
                }
                if (!isset(\Hyf::$config[$value['config']])) {
                    return new \stdClass();
                }
                return new $value['class']($value['config']);
            };
        }
    }
}