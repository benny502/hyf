<?php
namespace hyf\component\io;

class output
{

    public function success($data)
    {
        return '{"code": 0, "msg": "ok", "data": ' . json_encode($data) . '}';
    }

    public function error($msg, $data = [])
    {
        return '{"code": 0, "msg": "' . $msg . '", "data": ' . json_encode($data) . '}';
    }
}