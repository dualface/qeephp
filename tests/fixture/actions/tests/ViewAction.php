<?php

namespace tests\fixture\actions\tests;

use qeephp\mvc\BaseAction;

class ViewAction extends BaseAction
{
    function execute()
    {
        $this->view();
    }
}
