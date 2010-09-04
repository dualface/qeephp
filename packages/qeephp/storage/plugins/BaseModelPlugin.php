<?php

namespace qeephp\storage\plugins;

use qeephp\storage\Meta;

abstract class BaseModelPlugin
{
    public $config;

    function __construct(array $config)
    {
        $this->config = $config;
    }

    abstract function bind(Meta $meta);
}

