<?php
namespace hyf\component\db\redis;

class core
{

    public $redis;
    
    public $logfile;

    public function __construct($dbType = "redis")
    {
        $dbConf = \Hyf::$config[$dbType];
        $this->logfile = log_path() . 'rediserror.log';
        
        $this->redis = new \Redis();
        $connect = $this->redis->connect($dbConf['host'], $dbConf['port']);
        if(!$connect) {
            file_put_contents($this->logfile, date('Y-m-d H:i:s') . " redis连接失败\n", FILE_APPEND | LOCK_EX);
        }
        if (!empty($dbConf['auth'])) { // 需要认证
            $isauth = $this->redis->auth($dbConf['password']);
            if (!$isauth) {
                file_put_contents($this->logfile, date('Y-m-d H:i:s') . " redis认证失败\n", FILE_APPEND | LOCK_EX);
            }
        }
    }


    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->redis, $name], $arguments);
    }
}
