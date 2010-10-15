<?php

namespace tests\fixture;

use qeephp\mvc\App;

class TestApp extends App
{
    function __construct()
    {
        parent::__construct(__NAMESPACE__, __DIR__, false);
    }
}

