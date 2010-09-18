<?php

namespace tests\fixture\models\users;

use qeephp\storage\Meta;
use qeephp\storage\plugins\BaseModelPlugin;

class EmptyPlugin extends BaseModelPlugin
{
    function bind(Meta $meta)
    {
        $meta->add_static_method('test_empty_plugin_static_method', array($this, 'test_empty_plugin_static_method'));
    }

    function test_empty_plugin_static_method()
    {
        return 'test_empty_plugin_static_method';
    }
}

