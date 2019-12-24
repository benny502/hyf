<?php
namespace hyf\component\db\mysql;

class core
{

    public $dsn;

    public $dbuser;

    public $dbpass;

    public $strict;

    public $charset;

    public $sth;

    public $dbh;

    public $timeout;

    public $logfile;

    public function __construct($dbType = "mysql")
    {
        $dbConf = \Hyf::$config[$dbType];
        $this->logfile = log_path() . 'sqlerror.log';
        $this->dsn = "mysql:host={$dbConf['host']};port={$dbConf['port']};dbname={$dbConf['database']};";
        $this->dbuser = $dbConf['user'];
        $this->dbpass = $dbConf['password'];
        $this->timeout = $dbConf['timeout'];
        $this->strict = !empty($dbConf['strict']) ? $dbConf['strict'] : false;
        $this->charset = $dbConf['charset'];
        $this->connect();
    }

    private function connect()
    {
        try {
            $this->dbh = new \PDO($this->dsn, $this->dbuser, $this->dbpass, [
                \PDO::ATTR_TIMEOUT => $this->timeout
            ]);
            if (!empty($this->strict)) {
                $this->dbh->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
                $this->dbh->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            }
            if (!empty($this->charset)) {
                $this->dbh->query('SET NAMES ' . $this->charset);
            }
        } catch (\PDOException $e) {
            file_put_contents($this->logfile, date('Y-m-d H:i:s') . " " . $e->getMessage(), FILE_APPEND | LOCK_EX);
        }
    }

    public function getLastID()
    {
        return $this->dbh->lastInsertId();
    }

    private function getPDOError($sql)
    {
        if ($this->dbh->errorCode() != '00000') {
            file_put_contents($this->logfile, PHP_EOL . date('Y-m-d H:i:s') . " [" . $this->dbh->errorCode() . "]" . PHP_EOL . $sql . PHP_EOL . var_export($this->dbh->errorInfo(), true) . PHP_EOL, FILE_APPEND | LOCK_EX);
            return false;
        }
        return true;
    }

    public function query($sql, $model = 'many')
    {
        $this->sth = @$this->dbh->query($sql);
        if ($this->getPDOError($sql) == false) {
            $this->sth = $this->reconnect($sql);
        }
        $this->sth->setFetchMode(\PDO::FETCH_ASSOC);
        if ($model == 'many') {
            $result = $this->sth->fetchAll();
        } else {
            $result = $this->sth->fetch();
        }
        $this->sth = null;
        return $result;
    }

    public function exec($sql)
    {
        $rtn = @$this->dbh->exec($sql);
        if ($this->getPDOError($sql) == false) {
            $rtn = $this->reconnect($sql, 'exec');
        }
        return $rtn;
    }

    // yield obj
    public function yield_query($sql)
    {
        $result = @$this->dbh->query($sql);
        if ($this->getPDOError($sql) == false) {
            $result = $this->reconnect($sql);
        }
        while (($row = $result->fetch(\PDO::FETCH_ASSOC)) != false) {
            yield $row;
        }
    }

    public function reconnect($sql, $type = 'query')
    {
        $this->connect();
        $this->dbh->query('SET NAMES utf8');
        if ($type == 'query') {
            $result = @$this->dbh->query($sql);
        } else {
            $result = @$this->dbh->exec($sql);
        }
        file_put_contents($this->logfile, PHP_EOL . date('Y-m-d H:i:s') . " [reconnect]" . PHP_EOL . $sql . PHP_EOL, FILE_APPEND | LOCK_EX);
        return $result;
    }

    public function beginTransaction()
    {
        $this->dbh->beginTransaction();
    }

    public function commit()
    {
        $this->dbh->commit();
    }

    public function rollback()
    {
        $this->dbh->rollback();
    }

    public function __destruct() {
        $this->dbh = null;
    }
}
