<?php

namespace qeephp\storage\adapter;

use qeephp\tools\ILogger;
use qeephp\storage\BaseModel;
use qeephp\storage\Meta;

interface IAdapter
{
    /**
     * 设置日志服务对象
     *
     * @param ILogger $logger
     */
    function set_logger(ILogger $logger);

    /**
     * 清除日志服务对象
     */
    function remove_logger();

    /**
     * 按照指定条件查询一条记录并返回记录数组，如果没有查询到数据则返回 false
     *
     * @param string $table
     * @param mixed $cond
     * @param string|array $fields
     * @param array $alias
     *
     * @return array|bool
     */
    function find_one($table, $cond, $fields = null, array $alias = null);

    /**
     * 构造并返回一个查询对象
     *
     * @param string $table
     * @param mixed $cond
     * @param string|array $fields
     * @param array $alias
     *
     * @return IAdapterFinder
     */
    function find($table, $cond, $fields = null, array $alias = null);

    /**
     * 插入一条记录，并尝试返回主键值
     *
     * 如果没有指定 $idname 参数，
     *
     * @param string $table
     * @param array $values
     * @param array $alias
     *
     * @return mixed
     */
    function insert($table, array $values, array $alias = null);

    /**
     * 更新符合条件的记录，返回被更新的记录数
     *
     * @param string $table
     * @param mixed $cond
     * @param mixed $values
     * @param array $alias
     *
     * @return int
     */
    function update($table, $cond, $values, array $alias = null);

    /**
     * 更新模型
     *
     * @param BaseModel $model
     * @param Meta $meta
     */
    function update_model(BaseModel $model, Meta $meta);

    /**
     * 删除符合条件的记录，返回被删除记录的总数
     *
     * @param string $table
     * @param mixed $cond
     * @param array $alias
     *
     * @return int
     */
    function del($table, $cond, array $alias = null);
}

