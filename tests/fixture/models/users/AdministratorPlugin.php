<?php

namespace tests\fixture\models\users;

use qeephp\storage\Meta;
use qeephp\storage\BaseModel;
use qeephp\storage\plugins\BaseModelPlugin;

class AdministratorPlugin extends BaseModelPlugin
{
    function bind(Meta $meta)
    {
        $meta->add_static_method('test_admin_plugin_static_method', array($this, 'test_admin_plugin_static_method'));
        $meta->add_dynamic_method('test_admin_plugin_dynamic_method', array($this, 'test_admin_plugin_dynamic_method'));
    }

    function test_admin_plugin_static_method()
    {
        return !empty($this->config['arg1']);
    }

    function test_admin_plugin_dynamic_method(BaseModel $model)
    {
        return 'test_admin_plugin_dynamic_method';
    }
}

