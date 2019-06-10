<?php
namespace hyf\component\stdClass;

class std
{

    public function __call($method, $args)
    {
        if (isset($this->$method)) {
            return $this->$method;
        }
    }
}