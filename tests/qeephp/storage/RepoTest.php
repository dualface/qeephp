<?php

namespace tests\qeephp\storage;

use tests\includes\TestCase;
use tests\qeephp\fixture\StorageFixture;
use tests\qeephp\fixture\models\Post;

use qeephp\storage\Repo;
use qeephp\tools\Logger;

require_once __DIR__ . '/../__init.php';

class RepoTest extends TestCase
{
    private $_default_adapter;
    private $_recordset;

    /**
     * 根据模型定义的存储域选择存储适配器
     *
     * @api Repo::set_dispatcher()
     * @api Repo::select_adapter()
     */
    function test_select_adapter()
    {
        $domain = StorageFixture::DEFAULT_DOMAIN;
        $id = 1;

        /**
         * #BEGIN EXAMPLE
         *
         * 设定指定存储域的调度方法，并通过 select_adapter() 在运行时选择实际的存储适配器对象。
         *
         * -  自定义的调度函数对主键值取模，并返回存储域的节点名字。
         *    select_adapter() 根据调度函数的返回值构造完整的存储域名称，并返回相应的存储对象。
         *
         * -  例如主键值＝1，下述调度函数会产生节点名称 node1。最后实际的存储域名称就是 $domain.node1。
         *
         * -  如果指定的存储域没有指定调度函数，则返回该存储域对应的存储对象。
         */
        $dispatcher = function ($domain, $id) {
            $node_index = (($id - 1) % 2) + 1;
            return "node{$node_index}";
        };

        Repo::set_dispatcher($domain, $dispatcher);
        $adapter = Repo::select_adapter($domain, $id);
        // #END EXAMPLE

        $this->assertType('qeephp\\storage\\adapter\\IAdapter', $adapter);
        $config = $adapter->config;
        $this->assertEquals('qeephp_test_db1', $config['database']);

        $adapter_second = Repo::select_adapter(StorageFixture::DEFAULT_DOMAIN, 2);
        Repo::del_dispatcher(StorageFixture::DEFAULT_DOMAIN);
        $this->assertType('qeephp\\storage\\adapter\\IAdapter', $adapter_second);
        $config = $adapter_second->config;
        $this->assertEquals('qeephp_test_db2', $config['database']);
        $this->assertFalse($adapter === $adapter_second);
    }

    function test_find_one()
    {
        $post_id = 1;
        $class = 'tests\\qeephp\\fixture\\models\\Post';
        $post = Repo::find_one($class, $post_id);
        $this->_check_post($post, $post_id);

        $cond = array('post_id > ? AND post_id < ?', 1, 3);
        $post = Repo::find_one($class, $cond);
        $this->_check_post($post, 2);
    }

    function test_find_multi()
    {
        $post_id_list = array(1, 3, 5);
        $class = 'tests\\qeephp\\fixture\\models\\Post';
        $posts = Repo::find_multi($class, $post_id_list);
        $this->assertEquals(3, count($posts));
        $id = 1;
        foreach ($posts as $post_id => $post)
        {
            $this->_check_post($post, $post_id);
            $this->assertEquals($id, $post_id);
            $id += 2;
        }
    }

    function test_create()
    {
        $post = new Post();
        $post->postId = 99;
        $post->title  = 'post 99';
        $post->author = 'author 99';
        $post->click_count = 99;

        Repo::save($post);
        $record = $this->_get_post_record(99);
        $this->assertType('array', $record);
        $this->assertEquals(99, $record['post_id']);
        $this->assertEquals('post 99', $record['title']);
        $this->assertEquals('author 99', $record['author']);
        $this->assertEquals(99, $record['click_count']);
    }

    function test_simple_update()
    {

    }

    function test_update_changed_props()
    {
    }

    function test_update_prop_by_arithmetic()
    {
        $post_id = 1;
        $class = 'tests\\qeephp\\fixture\\models\\Post';
        $post = Repo::find_one($class, array('postId' => $post_id));
        $post->click_count += 100;
        $post->title = strrev($post->title);

        Repo::clean_cache();
        $post2 = Repo::find_one($class, array('postId' => $post_id));
        $post2->click_count += 100;

        $result = Repo::save($post);
        $result2 = Repo::save($post2);

        $this->assertEquals(1, $result);
        $this->assertEquals(1, $result2);

        $record = $this->_get_post_record($post_id);
        $this->assertType('array', $record);
        $this->assertEquals($post_id, $record['post_id']);
        $this->assertEquals(strrev($this->_recordset[$post_id]['title']), $record['title']);
        $this->assertEquals($this->_recordset[$post_id]['click_count'] + 200, $record['click_count']);
    }

    function test_del()
    {
        $this->markTestIncomplete();
    }

    function test_erase()
    {
        $this->markTestIncomplete();
    }

    protected function setup()
    {
        StorageFixture::set_default_mysql_domain_config();
        StorageFixture::set_second_domain_config();
        $this->_cleanup();

        $this->_default_adapter = Repo::select_adapter(StorageFixture::DEFAULT_NODE);
        $this->_default_adapter->set_logger(Logger::instance('test'));
        $meta = Post::meta();

        $this->_recordset = StorageFixture::post_recordset();
        foreach ($this->_recordset as $record)
        {
            $this->_default_adapter->insert($meta->collection, $record);
        }
    }

    protected function teardown()
    {
        $this->_cleanup();
    }

    private function _cleanup()
    {
        $adapter = Repo::select_adapter(Post::meta()->domain());
        $adapter->execute('DELETE FROM post');
        $this->_default_adapter = null;
        $this->_recordset = null;
        Repo::clean_cache();
    }

    private function _check_post($post, $post_id)
    {
        $this->assertType(Post::meta()->class, $post);
        $record = $this->_recordset[$post_id];
        $record['postId'] = $post_id;
        unset($record['post_id']);
        foreach ($record as $key => $value)
        {
            $this->assertEquals($value, $post->$key);
        }
    }

    private function _get_post_record($post_id)
    {
        $meta = Post::meta();
        $cond = array($meta->props_to_fields[$meta->idname] => $post_id);
        return $this->_default_adapter->find_one($meta->collection, $cond);
    }
}

