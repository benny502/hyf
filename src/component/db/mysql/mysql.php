<?php
namespace hyf\component\db\mysql;

use hyf\component\db\mysql\core;

class mysql
{
    public function __call($method, $args)
    {
        if (!isset($this->$method)) {
            $this->$method = new core($method);
        }
        return $this->$method;
    }
}
