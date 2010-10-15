<?php

namespace tests\fixture\actions;

use qeephp\mvc\BaseAction;

class HelpAction extends BaseAction
{
    function execute()
    {
        echo 'helpAction';
    }
}
