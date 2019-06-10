<?php

/**
 * swoole memory table 
 * @author
 *
 */
namespace hyf\component\memory;

class table
{

    // table
    public $table;

    // 表格的最大行数
    public $size;

    /**
     * table column
     * $column = [
     * ['name' => 'test1', 'type' => 'int', 'size' => 8],
     * ['name' => 'test2', 'type' => 'string', 'size' => 256],
     * ['name' => 'test3', 'type' => 'float']
     * ];
     *
     * @var array
     */
    public $column = [];

    public function __construct(array $column, $size = 1024)
    {
        $this->size = $size;
        $this->column = $column;
        
        $this->tableCreate();
    }

    public function tableCreate()
    {
        $this->table = new \Swoole\Table($this->size);
        foreach ($this->column as $column) {
            if (isset($column['size'])) {
                $this->table->column($column['name'], $this->columnType($column['type']), $column['size']);
            } else {
                $this->table->column($column['name'], $this->columnType($column['type']));
            }
        }
        $this->table->create();
    }

    public function columnType($type)
    {
        switch ($type) {
            case 'int':
                return \Swoole\Table::TYPE_INT; // 默认为4个字节，可以设置1，2，4，8一共4种长度
            case 'string':
                return \Swoole\Table::TYPE_STRING; // 设置的字符串不能超过此长度
            case 'float':
                return \Swoole\Table::TYPE_FLOAT; // 会占用8个字节的内存
            default:
                return false;
        }
    }

    /**
     * set table value
     *
     * @param string $key            
     * @param array $value
     *            数组元素依次为column中配置的列
     */
    public function set(string $key, array $value)
    {
        $this->table->set($key, $value);
    }

    /**
     *
     * @param string $key
     *            如果$key不存在，将返回false
     * @param string $field
     *            当指定了$field时仅返回该字段的值，而不是整个记录
     * @return array|boolean|string
     */
    public function get(string $key, string $field = null)
    {
        return $this->table->get($key, $field);
    }

    /**
     * 检查table中是否存在某一个key
     *
     * @param string $key            
     * @return boolean
     */
    public function exists(string $key)
    {
        return $this->table->exist($key);
    }

    /**
     * 删除数据，对应的数据不存在，将返回false，成功返回true
     *
     * @param string $key            
     * @return boolean
     */
    public function del(string $key)
    {
        return $this->table->del($key);
    }

    /**
     * 返回table中元素个数
     *
     * @return number
     */
    public function count()
    {
        return $this->table->count();
    }

    /**
     *
     * @return mixed
     */
    public function destroy()
    {
        return $this->table->destroy();
    }

    /**
     *
     * @return mixed
     */
    public function getMemorySize()
    {
        return $this->table->getMemorySize();
    }

    /**
     *
     * @param
     *            $offset[required]
     * @return mixed
     */
    public function offsetExists($offset)
    {
        return $this->table->offsetExists($offset);
    }

    /**
     *
     * @param
     *            $offset[required]
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->table->offsetGet($offset);
    }

    /**
     *
     * @param
     *            $offset[required]
     * @param
     *            $value[required]
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        return $this->table->offsetSet($offset, $value);
    }

    /**
     *
     * @param
     *            $offset[required]
     * @return mixed
     */
    public function offsetUnset($offset)
    {
        return $this->table->offsetUnset($offset);
    }

    /**
     *
     * @return mixed
     */
    public function rewind()
    {
        return $this->table->rewind();
    }

    /**
     *
     * @return mixed
     */
    public function next()
    {
        return $this->table->next();
    }

    /**
     *
     * @return mixed
     */
    public function current()
    {
        return $this->table->current();
    }

    /**
     *
     * @return mixed
     */
    public function key()
    {
        return $this->table->key();
    }

    /**
     *
     * @return mixed
     */
    public function valid()
    {
        return $this->table->valid();
    }
}

// test
/*$column = [
    [
        'name' => 'name',
        'type' => 'string',
        'size' => 50
    ],
    [
        'name' => 'age',
        'type' => 'int',
        'size' => 2
    ],
    [
        'name' => 'sex',
        'type' => 'string',
        'size' => 5
    ]
];
$table = new table($column);
$table->set('1', [
    'name' => '张三',
    'age' => 18,
    'sex' => '男'
]);
$table->set('hello', [
    'name' => '李四',
    'age' => 40,
    'sex' => '男'
]);
$table->set('fuck', [
    'name' => '王麻子',
    'age' => 56,
    'sex' => '女'
]);

var_dump($table->get('hello'), $table->get('fuck'), $table->get('1'));

if ($table->exists('world')) {
    echo "hello\n";
} else {
    echo "none\n";
}

var_dump($table->count());

$table->del('fuck');

if ($table->exists('fuck')) {
    echo "fuck\n";
} else {
    echo "no fuck\n";
}*/

