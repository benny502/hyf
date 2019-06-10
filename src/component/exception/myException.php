<?php
namespace hyf\component\exception;

class myException extends \Exception {
    
    public function show() {
        return '{"code": 1, "msg": "'.$this->getMessage().'", "data": []}';
    }
    
}