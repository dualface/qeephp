<?php

namespace qeephp\mvc;

class Request
{
    public $get;
    public $post;
    public $cookie;
    public $session;

    function __construct($get, $post, $cookie, $session)
    {
        $this->get = $get;
        $this->post = $post;
        $this->cookie = $cookie;
        $this->session = $session;
    }

    function get($name, $default = null)
    {
        return isset($this->get[$name]) ? $this->get[$name] : $default;
    }

    function post($name, $default = null)
    {
        return isset($this->post[$name]) ? $this->post[$name] : $default;
    }

    function cookie($name, $default = null)
    {
        return isset($this->cookie[$name]) ? $this->cookie[$name] : $default;
    }

    function session($name, $default = null)
    {
        return isset($this->session[$name]) ? $this->session[$name] : $default;
    }

    function filter($source, $rule)
    {

    }

    function filter_all($batch)
    {

    }
}


