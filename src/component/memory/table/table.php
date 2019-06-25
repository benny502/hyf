<?php
namespace hyf\component\memory\table;

use hyf\component\memory\table\core;

class table
{
    public function __call($method, $args)
    {
        if (!isset($this->$method)) {
            $class = "\\application\\" . app_name() . "\\conf\\table";
            if(empty($class::$$method)){
                return NULL;
            }
            $this->$method = new core($class::$$method);
        }
        return $this->$method;
    }
}
