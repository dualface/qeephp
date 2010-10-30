<?php

namespace tests\fixture\tools;

class OtherTool
{
    public $config;

    function __construct($app, $config)
    {
        $this->config = $config;
    }
}
