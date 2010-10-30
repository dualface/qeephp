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
    function __construct(App $app, $name)
    {
        $this->app = $app;
        $this->name = $name;
    }

    /**
     * 执行动作
     */
    function __execute()
    {
        if (!$this->__before_execute()) return;
        $result = $this->execute();
        if (!is_null($result)) $this->result = $result;
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

