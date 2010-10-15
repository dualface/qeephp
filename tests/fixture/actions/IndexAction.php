<?php

namespace tests\fixture\actions;

use qeephp\mvc\BaseAction;

class IndexAction extends BaseAction
{
    function execute()
    {
        echo 'indexAction';
    }
}
