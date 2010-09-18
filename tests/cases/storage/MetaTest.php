<?php

namespace tests\cases\storage;

use tests\includes\TestCase;
use tests\fixture\ModelTestApp;
use tests\fixture\models\Post;
use tests\fixture\models\Comment;
use tests\fixture\models\Revision;
use tests\fixture\models\LogEntity;
use tests\fixture\models\users\BaseUser;
use tests\fixture\models\users\Guest;
use tests\fixture\models\users\Member;
use tests\fixture\models\users\Administrator;

use qeephp\storage\IStorageDefine;
use qeephp\storage\Meta;

require __DIR__ . '/../../__init.php';

class MetaTest extends TestCase
{
    private $_app;

    private function _check_props($props, $meta)
    {
        $arr = arr($meta->class, '\\');
        $class = array_pop($arr);
        foreach ($props as $name)
        {
            $this->assertArrayHasKey($name, $meta->props,
                    "{$class}::\${$name} not in \$meta->props['{$name}']");

            $prop = $meta->props[$name];
            if ($prop['nonp']) continue;

            $this->assertArrayHasKey($name, $meta->props_to_fields,
                    "{$class}::\${$name} not in \$meta->props_to_fields['{$name}']");
            $field = $meta->props_to_fields[$name];
            $this->assertArrayHasKey($field, $meta->fields_to_props,
                    "{$class}::\${$name} field name is '{$field}', not found \$meta->fields_to_props['{$field}']");
            if ($prop['update'] != Meta::UPDATE_PROP_OVERWRITE)
            {
                $this->assertArrayHasKey($name, $meta->spec_update_props,
                        "{$class}::\${$name} tagged @update, not found \$meta->spec_update_props['{$name}']");
                $this->assertEquals($prop['update'], $meta->spec_update_props[$name]);
            }

        }
    }

    function test_inspect_class()
    {
        // class: @domain, @collection, @update, @readonly
        $meta = new Meta('tests\\fixture\\models\\Post');
        $this->assertEquals(Post::TEST_DOMAIN, $meta->domain());
        $this->assertEquals(Post::TEST_COLLECTION, $meta->collection());
        $this->assertEquals(Post::TEST_UPDATE, $meta->update);
        $this->assertEquals(Post::TEST_READONLY, $meta->readonly);
        $this->assertEquals(Post::TEST_IDNAME, $meta->idname);
        $this->assertEquals(Post::TEST_AUTOINCR_IDNAME, $meta->autoincr_idname);

        // props
        $props = array('postId', 'title', 'author', 'value', 'click_count');
        $this->_check_props($props, $meta);

        // prop: @id, @var
        $prop = $meta->props['postId'];
        $this->assertTrue($prop['id']);
        $this->assertFalse($prop['autoincr']);
        $this->assertEquals(Meta::TYPE_INT, $prop['type']);
        $this->assertEquals(null, $prop['len']);

        // prop: @nonp
        $prop = $meta->props['value'];
        $this->assertTrue($prop['nonp']);

        // prop: @internal
        $this->assertFalse(isset($meta->props['__admin_uid']));

        // prop: @var type(len)
        $prop = $meta->props['title'];
        $this->assertEquals(Post::TEST_PROP_TITLE_TYPE, $prop['type']);
        $this->assertEquals(Post::TEST_PROP_TITLE_LEN, $prop['len']);

        // prop: @update
        $prop = $meta->props['click_count'];
        $this->assertEquals(Post::TEST_PROP_CLICK_COUNT_UPDATE, $prop['update']);

        // class
        $meta = new Meta('tests\\fixture\\models\\Comment');
        $this->assertEquals(Comment::TEST_COLLECTION,   $meta->collection());
        $this->assertEquals(Comment::TEST_UPDATE,       $meta->update);
        $this->assertEquals(Comment::TEST_READONLY,     $meta->readonly);
        $this->assertEquals(Comment::TEST_IDNAME,       $meta->idname);
        $this->assertEquals(Comment::TEST_AUTOINCR_IDNAME,  $meta->autoincr_idname);

        $props = array('comment_id', 'post_id', 'body', 'author', 'created');
        $this->_check_props($props, $meta);

        // prop: @optional
        $prop = $meta->props['author'];
        $this->assertTrue($prop['optional']);
        $this->assertFalse($prop['readonly']);
        $this->assertEquals(Comment::TEST_PROP_AUTHOR_DEFAULT, $prop['default']);

        // prop: @readonly, @fill_on_create
        $prop = $meta->props['created'];
        $this->assertEquals(Meta::TYPE_INT, $prop['type']);
        $this->assertTrue($prop['readonly']);

        // prop: @nonp, @getter
        $prop = $meta->props['created_string'];
        $this->assertTrue($prop['nonp']);
        $this->assertEquals('get_created_string', $prop['getter']);

        // class: 复合主键（有一个主键是自增）
        $meta = new Meta('tests\\fixture\\models\\Revision');
        $props = array('postId', 'rev_id');
        $this->_check_props($props, $meta);

        $this->assertEquals(array('postId', 'rev_id'), $meta->idname);
        $this->assertEquals('rev_id', $meta->autoincr_idname);
        $this->assertEquals(IStorageDefine::UPDATE_ALL_PROPS, $meta->update);
        $this->assertTrue($meta->composite_id);

        // class: 复合主键（无自增）
        $meta = new Meta('tests\\fixture\\models\\LogEntity');
        $props = array('log_id', 'user_id');
        $this->_check_props($props, $meta);

        $this->assertEquals(array('log_id', 'user_id'), $meta->idname);
        $this->assertEquals(null, $meta->autoincr_idname);
        $this->assertTrue($meta->composite_id);
    }

    function test_inherited_class()
    {
        $meta = new Meta('tests\\fixture\\models\\users\\Member');
        $this->assertTrue($meta->use_extends);
        $extends = $meta->extends;
        $this->assertType('array', $extends);
        $this->assertArrayHasKey('by', $extends);
        $this->assertArrayHasKey('classes', $extends);
        $classes = $extends['classes'];
        $this->assertType('array', $classes);
        foreach ($classes as $offset => $class_name)
        {
            $this->assertType('int', $offset);
            $this->assertType('string', $class_name);
        }
    }

    function test_bind_plugins()
    {
        $meta = new Meta('tests\\fixture\\models\\users\\Administrator');
        $this->assertArrayHasKey('test_admin_plugin_static_method', $meta->static_methods);
        $this->assertArrayHasKey('test_admin_plugin_dynamic_method', $meta->dynamic_methods);
        $this->assertArrayHasKey('test_empty_plugin_static_method', $meta->static_methods);
        $result = call_user_func($meta->static_methods['test_admin_plugin_static_method']);
        $this->assertTrue($result);
    }

    function test_events()
    {
        $meta = new Meta('tests\\fixture\\models\\users\\Member');
        $listener = function ($event) {
            $event->completed = true;
            $event->result = 1;
        };
        $meta->add_event_listener(Meta::BEFORE_CREATE_EVENT, $listener);
        $event = $meta->raise_event(Meta::BEFORE_CREATE_EVENT);
        $this->assertType('qeephp\\Event', $event);
        $this->assertTrue($event->completed);
        $this->assertTrue($event->continue);
        $this->assertEquals(1, $event->result);

        $meta->remove_event_listener(Meta::BEFORE_CREATE_EVENT, $listener);
        $event = $meta->raise_event(Meta::BEFORE_CREATE_EVENT);
        $this->assertFalse($event);
    }

    protected function setup()
    {
        $this->_app = new ModelTestApp();
    }

    protected function teardown()
    {
        $this->_app = null;
    }
}

