<?php

namespace qeephp\mvc;

/**
 * 封装一个请求
 */
class Request
{
    /**
     * GET 数据
     *
     * @var array
     */
    public $get;

    /**
     * POST 数据
     *
     * @var array
     */
    public $post;

    /**
     * COOKIE 数据
     *
     * @var array
     */
    public $cookie;

    /**
     * SESSION 数据
     *
     * @var array
     */
    public $session;

    function __construct($get, $post, $cookie, $session)
    {
        $this->get     = $get;
        $this->post    = $post;
        $this->cookie  = $cookie;
        $this->session = $session;
    }

    /**
     * 从 GET 取得数据，如果指定数据不存在则返回 $default 指定的默认值
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    function get($name, $default = null)
    {
        return isset($this->get[$name]) ? $this->get[$name] : $default;
    }

    /**
     * 从 POST 取得数据，如果指定数据不存在则返回 $default 指定的默认值
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    function post($name, $default = null)
    {
        return isset($this->post[$name]) ? $this->post[$name] : $default;
    }

    /**
     * 从 COOKIE 取得数据，如果指定数据不存在则返回 $default 指定的默认值
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    function cookie($name, $default = null)
    {
        return isset($this->cookie[$name]) ? $this->cookie[$name] : $default;
    }

    /**
     * 从 SESSION 取得数据，如果指定数据不存在则返回 $default 指定的默认值
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    function session($name, $default = null)
    {
        return isset($this->session[$name]) ? $this->session[$name] : $default;
    }
}


