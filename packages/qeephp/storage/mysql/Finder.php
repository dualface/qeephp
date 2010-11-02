<?php

namespace qeephp\storage\mysql;

use qeephp\storage\Meta;
use qeephp\storage\IFinder;

class Finder implements IFinder
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
    private $_meta;
    private $_query_completed = false;

    function __construct(DataSource $adapter, $collection, $cond, $fields = null, array $alias = null)
    {
        $this->_adapter = $adapter;
        $this->_collection = $collection;
        $this->_cond = $cond;
        $this->_fields = $fields;
        $this->_alias = $alias;
    }

    function  __destruct()
    {
        $this->_free();
    }

    /**
     * 为查询结果指定模型类
     *
     * @param string $class
     *
     * @return Finder
     */
    function set_model_class($class)
    {
        $this->_class = $class;
        $this->_meta  = Meta::instance($class);
        return $this;
    }

    /**
     * 设定查询的排序
     *
     * @param mixed $sort
     *
     * @return Finder
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
     * @return Finder
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
     * @return Finder
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
        return $this->_record($record);
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
            $records[] = $this->_record($record);
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
            call_user_func($func, $this->_record($record));
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

    protected function _record(array $record)
    {
        if (!$this->_class) return $record;
        return $this->_meta->props_to_model($this->_meta->fields_to_props($record));
    }
}

