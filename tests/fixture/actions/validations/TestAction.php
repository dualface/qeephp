<?php

namespace tests\fixture\actions\validations;

use qeephp\mvc\BaseAction;

/**
 * 用于测试 Action 数据过滤
 */
class TestAction extends BaseAction
{

    function execute()
    {
        $this->result[] = 'execute';
    }

    function validate_input()
    {
        if (parent::validate_input())
        {
            $this->result[] = 'validate_input';
            if ($this->request->get('failed') == 'input')
            {
                $this->result[] = 'validate_input_failed';
                return false;
            }
            return true;
        }
        return false;
    }

    function validate_output()
    {
        if (parent::validate_output())
        {
            $this->result[] = 'validate_output';
            if ($this->request->get('failed') == 'output')
            {
                $this->result[] = 'validate_output_failed';
                return false;
            }
            return true;
        }
        return false;
    }

    protected function __before_execute()
    {
        if (parent::__before_execute())
        {
            $this->result = array();
            return true;
        }
        return false;
    }
}

