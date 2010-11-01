<?php

namespace qeephp\mvc;

/**
 * 动作对象基础类
 */
abstract class BaseAction
{
    /**
     * 应用程序对象
     *
     * @var App
     */
    public $app;

    /**
     * 动作名称
     *
     * @var string
     */
    public $name;

    /**
     * 当前请求
     *
     * @var Request
     */
    public $request;

    /**
     * 执行结果
     *
     * @var mixed
     */
    public $result;

    /**
     * 构造函数
     *
     * @param App $app
     * @param string $name
     */
    function __construct($app, $name)
    {
        $this->app = $app;
        $this->name = $name;
        $this->request = $app->request;
    }

    /**
     * 执行动作
     */
    function __execute()
    {
        if (!$this->__before_execute()) return;
        if ($this->validate_input())
        {
            $result = $this->execute();
            if (!is_null($result)) $this->result = $result;
            if (!$this->validate_output())
            {
                $this->on_validate_output_failed();
            }
        }
        else
        {
            $this->on_validate_input_failed();
        }
        $this->__after_execute();
    }

    /**
     * 执行指定的视图对象
     *
     * @param array $vars
     */
    function view(array $vars = null)
    {
        if (!is_array($vars)) $vars = array();
        $this->result = $this->app->view($this->name, $vars);
    }

    /**
     * 应用程序执行的动作内容，在继承的动作对象中必须实现此方法
     * 
     * 返回值会被保存到动作对象的 $result 属性中。
     *
     * @return mixed
     */
    abstract function execute();

    /**
     * 继承类覆盖此方法，用于在执行请求前过滤并验证输入数据
     *
     * 如果返回 false 则阻止调用 execute() 方法，并调用 validate_input_failed() 方法。
     *
     * @return bool
     */
    function validate_input()
    {
        return true;
    }

    /**
     * 继承类覆盖此方法，用于在执行请求后过滤并验证输出数据
     *
     * 如果返回 false 则调用 validate_output_failed() 方法。
     *
     * @return bool
     */
    function validate_output()
    {
        return true;
    }

    /**
     * 请求前对数据进行验证失败时调用此方法
     */
    function on_validate_input_failed()
    {
    }

    /**
     * 请求执行后对数据进行验证失败时调用此方法
     */
    function on_validate_output_failed()
    {
    }

    /**
     * 执行动作之前调用，如果返回 false 则阻止动作的执行
     *
     * @return bool
     */
    protected function __before_execute()
    {
        return true;
    }

    /**
     * 执行动作之后调用
     */
    protected function __after_execute()
    {
    }
}

