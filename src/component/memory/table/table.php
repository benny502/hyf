<?php
namespace hyf\component\memory\table;

use hyf\component\memory\table\core;

class table
{

    public function __construct()
    {
        $class = "\\application\\" . app_name() . "\\conf\\table";
        if (class_exists($class) && !empty($class::$column)) {
            foreach ($class::$column as $key => $value) {
                $this->$key = new core($value);
            }
        }
    }

    public function __call($method, $args)
    {
        if (!isset($this->$method)) {
            return NULL;
        }
        return $this->$method;
    }
}
