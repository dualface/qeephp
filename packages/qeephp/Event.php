<?php

namespace qeephp;


/**
 * 事件
 */
class Event
{
    /**
     * 事件名称
     *
     * @var string
     */
    public $name;

    /**
     * 事件响应方法
     *
     * @var array
     */
    public $listeners;

    /**
     * 指示事件是否已经处理
     *
     * @var bool
     */
    public $completed = false;

    /**
     * 是否继续传递事件
     *
     * @var bool
     */
    public $continue = true;

    /**
     * 事件的执行结果
     *
     * @var mixed
     */
    public $result;

    /**
     * 构造函数
     *
     * @param string $name
     * @param array $listeners
     */
    function __construct($name, array $listeners = null)
    {
        $this->name = $name;
        $this->listeners = $listeners;
    }

    /**
     * 追加事件处理方法
     *
     * @param callback $listener
     *
     * @return Event
     */
    function append_listener($listener)
    {
        $this->listeners[] = $listener;
        return $this;
    }

    /**
     * 调度事件
     */
    function dispatching()
    {
        $this->dispatching_with_args(func_get_args());
    }

    /**
     * 使用多个参数调度事件
     *
     * @param array $args
     */
    function dispatching_with_args(array $args = null)
    {
        if (!is_array($args)) $args = array();
        array_unshift($args, $this);
        foreach ($this->listeners as $listener)
        {
            call_user_func_array($listener, $args);
            if (!$this->continue) break;
        }
    }
}
