<?php

namespace tests\fixture\actions\tests;

use qeephp\mvc\BaseAction;

class EmptyAction extends BaseAction
{
    function execute()
    {
        echo 'tests.emptyAction';
    }
}
