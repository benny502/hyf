<?php
namespace hyf\component\db\redis;

use hyf\component\db\redis\core;

class redis
{
    public function __call($method, $args)
    {
        if (!isset($this->$method)) {
            $this->$method = new core($method);
        }
        return $this->$method;
    }
}
