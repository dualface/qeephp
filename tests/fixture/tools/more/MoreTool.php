<?php

namespace tests\fixture\tools\more;

class MoreTool
{
    public $config;

    function __construct($app, $config)
    {
        $this->config = $config;
    }
}
