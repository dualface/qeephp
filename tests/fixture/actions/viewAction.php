<?php

namespace tests\fixture\actions;

use qeephp\mvc\BaseAction;

class ViewAction extends BaseAction
{
    function execute()
    {
        $this->view();
    }
}
