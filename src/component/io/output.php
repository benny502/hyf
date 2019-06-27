<?php
namespace hyf\component\io;

class output
{

    public function success($data)
    {
        return '{"ret": 0, "msg": "ok", "data": ' . json_encode($data) . '}';
    }

    public function error($msg, $code = 1, $data = [])
    {
        return '{"ret": ' . $code . ', "msg": "' . $msg . '", "data": ' . json_encode($data) . '}';
    }
}