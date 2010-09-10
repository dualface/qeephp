<?php

namespace qeephp\storage\adapter;

use qeephp\storage\Meta;

class MySQLFinder implements IAdapterFinder
{
    private $_adapter;
    private $_collection;
    private $_cond;
    private $_fields;
    private $_alias;
    private $_sort;
    private $_skip;
    private $_limit;
    private $_result;
    private $_class;
    private $_query_completed = false;

    function __construct(MySQLAdapter $adapter, $collection, $cond, $fields = null, array $alias = null)
    {
        $this->_adapter = $adapter;
        $this->_collection = $collection;
        $this->_cond = $cond;
        $this->_fields = $fields;
        $this->_alias = $alias;
    }

    /**
     * 为查询结果指定模型类
     *
     * @param string $class
     *
     * @return MySQLFinder
     */
    function set_model_class($class)
    {
        $this->_class = $class;
        return $this;
    }

    /**
     * 设定查询的排序
     *
     * @param mixed $sort
     *
     * @return MySQLFinder
     */
    function sort($sort)
    {
        $this->_sort = $sort;
        return $this;
    }

    /**
     * 设定查询结果跳过多少条记录
     *
     * @param int $skip
     *
     * @return MySQLFinder
     */
    function skip($skip)
    {
        $this->_skip = $skip;
        return $this;
    }

    /**
     * 限定查询结果的记录数
     *
     * @param int $limit
     * 
     * @return MySQLFinder
     */
    function limit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * 提取一个查询结果，如果没有更多结果则返回 false
     *
     * @return array|bool
     */
    function fetch()
    {
        if (!$this->_query_completed) $this->_query();
        if (!$this->_result) return false;
        $record = mysql_fetch_assoc($this->_result);
        if (!is_array($record))
        {
            $this->_free();
            return $record;
        }
        return $this->__record($record);
    }

    /**
     * 提取所有查询结果，如果没有结果则返回空数组
     *
     * @return array
     */
    function fetch_all()
    {
        if (!$this->_query_completed) $this->_query();
        if (!$this->_result) return array();
        $records = array();
        while ($record = mysql_fetch_assoc($this->_result))
        {
            $records[] = $this->__record($record);
        }
        $this->_free();
        return $records;
    }

    /**
     * 迭代每一个结果
     *
     * @param callback $func
     */
    function each($func)
    {
        if (!$this->_query_completed) $this->_query();
        if (!$this->_result) return;
        while ($record = mysql_fetch_assoc($this->_result))
        {
            call_user_func($func, $this->__record($record));
        }
        $this->_free();
    }

    private function _query()
    {
        $this->_result = $this->_adapter->select($this->_collection,
                                                 $this->_cond,
                                                 $this->_fields,
                                                 $this->_sort,
                                                 $this->_skip,
                                                 $this->_limit,
                                                 $this->_alias);
        $this->_query_completed = true;
    }

    private function _free()
    {
        if ($this->_result)
        {
            mysql_free_result($this->_result);
            $this->_result = null;
        }
    }

    protected function __record(array $record)
    {
        if (!$this->_class) return $record;
        $model = new $this->_class();
        $model->__read(Meta::instance($this->_class)->fields_to_props($record));
        return $model;
    }
}

