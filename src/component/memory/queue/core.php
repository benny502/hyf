<?php

namespace hyf\component\memory\queue;

class core
{
    public $size;

    public $key;

    public $msg_queue;

    public function __construct($key = '', $size = 1024)
    {
        $this->key = empty($key) ? ftok(__FILE__, 'a') : $key;

        $this->size = $size;

        $this->msg_queue = msg_get_queue($this->key, 0666);
    }

    //检测队列是否存在 ,返回boolean值
    public function exists()
    {
        return msg_queue_exists($this->key);
    }

    //查看当前队列的一些详细信息
    public function status()
    {
        return msg_stat_queue($this->msg_queue);
    }

    /**
     * 向队列插入msg
     *
     * @param $msg (可为任意类型 字符串、数组、对象等)
     * @return boolen
     */
    public function push($msg)
    {
        return msg_send($this->msg_queue, 1, $msg, true, true, $error_code);
    }

    /**
     * 弹出队列消息
     * 如果队列中无消息，则等待
     *
     * @return void
     */
    public function pop()
    {
        $ret = msg_receive($this->msg_queue, 0, $message_type, $this->key, $msg, true, 1, $error_code);
        if (!$ret || !$msg) {
            return false;
        }
        return $msg;
    }

    /**
     * remove queue
     *
     * @return void
     */
    public function remove()
    {
        msg_remove_queue($this->msg_queue);
    }
}
