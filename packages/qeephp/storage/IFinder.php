<?php

namespace qeephp\storage;

interface IFinder
{
    /**
     * 为查询结果指定模型类
     *
     * @param string $class
     *
     * @return IFinder
     */
    function set_model_class($class);

    /**
     * 提取一个查询结果，如果没有更多结果则返回 false
     *
     * @return array|bool
     */
    function fetch();

    /**
     * 提取所有查询结果，如果没有结果则返回空数组
     *
     * @return array
     */
    function fetch_all();

    /**
     * 迭代每一个结果
     *
     * @param callback $func
     */
    function each($func);
}

