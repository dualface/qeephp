<?php

namespace tests\qeephp\fixture;

use \qeephp\mvc\App;

class QeePHPTestApp extends App
{
    function __construct(array $config)
    {
        parent::__construct('tests\\qeephp', __DIR__, $config);
    }
}

