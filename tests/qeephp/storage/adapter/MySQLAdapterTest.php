<?php

namespace tests\qeephp\storage\adapter;

use tests\qeephp\fixture\StorageFixture;
use tests\includes\TestCase;

use qeephp\storage\adapter\MySQLAdapter;
use qeephp\tools\Logger;

require_once __DIR__ . '/../../__init.php';

class MySQLAdapterTest extends TestCase
{
    /**
     * @var MySQLAdapter
     */
    private $_mysql;
    private $_handle;

    function test_is_connected()
    {
        $this->assertTrue($this->_mysql->is_connected());
    }

    function test_handle()
    {
        $this->assertType('resource', $this->_mysql->handle());
    }

    /**
     * 按照指定条件查询，返回符合条件的第一个记录，如果查询结果为空则返回 false
     *
     * @api MySQLAdapter::find_one()
     */
    function test_find_one()
    {
        $mysql = $this->_mysql;

        /**
         * #BEGIN EXAMPLE
         * 
         * 查询条件有四种写法：
         * 
         * -  array(field => value, ...) 可以指定任意多个字段
         *    生成的查询条件是 WHERE field = value AND field2 = value2
         * 
         * -  array('查询条件', 查询参数)
         *    例如：array('post_id = ? OR title = ?', 1, 'post 1')
         *    生成：WHERE post_id = 1 OR title = 'post 1'
         *
         *    例如：array('post_id IN (?)', array(...))
         *    生成：WHERE post_id IN (...)
         *
         * -  array(field => array(value, ...))
         *    生成：WHERE field IN (...)
         *
         * -  字符串指定的任意查询条件
         */
        $post1 = $mysql->find_one('post', 'post_id = 1');
        $post2 = $mysql->find_one('post', array('post_id = ? OR title = ?', 1, 'post 1'));
        $post3 = $mysql->find_one('post', 'post_id = 1', 'post_id, title');
        // #END EXAMPLE

        $checks = array($post1, $post2, $post3);
        foreach ($checks as $post)
        {
            $this->assertType('array', $post);
            $this->assertArrayHasKey('post_id', $post);
            $this->assertEquals(1, $post['post_id']);
            $this->assertArrayHasKey('title', $post);
            $this->assertEquals('post 1', $post['title']);
        }

        $this->assertEquals(2, count($post3));
    }

    /**
     * 按照指定条件进行查询，并返回实现了 MySQLFinder 接口的查询对象
     *
     * @api MySQLAdapter::find()
     * @api MySQLFinder::sort()
     * @api MySQLFinder::skip()
     * @api MySQLFinder::limit()
     * @api MySQLFinder::fetch()
     * @api MySQLFinder::fetch_all()
     * @api MySQLFinder::each()
     */
    function test_find()
    {
        $mysql = $this->_mysql;

        /**
         * #BEGIN EXAMPLE
         *
         * 一次性查询多条记录，返回一个实现了 MySQLFinder 接口的查询结果对象。
         * 然后利用 MySQLFinder::fetch() 方法提取所有数据。
         */
        // 查询 post_id 为 1, 3, 5 的记录
        $finder = $mysql->find('post', array('post_id IN (?)', array(1, 3, 5)));
        $posts1 = array();
        while ($post = $finder->fetch())
        {
            $posts1[] = $post;
        }
 
        // 查询 post_id 为 2, 4, 6 的记录
        $posts2 = $mysql->find('post', array('post_id' => array(2, 4, 6)))->fetch_all();

        // 查询所有 post_id <= 3 的记录，并用 MySQLFinder::fetch_all() 方法取出所有查询结果
        $posts3 = $mysql->find('post', 'post_id <= 3', 'post_id, title')->fetch_all();

        /**
         * 查询 post_id <= 10 的记录；
         * 按照 title 进行排序；
         * 只取中间 4 条；
         * 将这些记录的 title 字段放入 $titles 数组
         */
        $titles = array();
        $func = function ($record) use (& $titles) {
            $titles[] = $record['title'];
        };
        $mysql->find('post', array('post_id <= ?', 10), 'title')
              ->sort('title ASC')
              ->skip(3)
              ->limit(4)
              ->each($func);
        // #END EXAMPLE

        $checks = array($posts1, $posts2, $posts3);
        foreach ($checks as $offset => $posts)
        {
            $this->assertType('array', $posts);
            $this->assertEquals(3, count($posts));
            foreach ($posts as $post)
            {
                $this->assertType('array', $post);
                $this->assertArrayHasKey('post_id', $post);
                $this->assertArrayHasKey('title', $post);
                
                if ($offset == 2)
                {
                    $this->assertEquals(2, count($post));
                }
                else
                {
                    $this->assertGreaterThan(2, count($post));
                }
            }
        }

        $this->assertEquals(4, count($titles));
        foreach ($titles as $offset => $title)
        {
            $this->assertEquals('post ' . (4 + $offset), $title);
        }
    }

    /**
     * 插入一条记录
     *
     * @api MySQLAdapter::insert()
     */
    function test_insert()
    {
        $mysql = $this->_mysql;

        /**
         * #BEGIN EXAMPLE
         *
         * 插入记录时，如果有自增类型的主键字段，可以不提供该字段的值。
         *
         * 插入记录后，如果有自增类型的主键字段，则返回该字段的新值。
         * 如果没有自增类型的主键字段，则返回 true 指示插入操作已经成功。
         */
        // post 表的 post_id 字段是自增类型主键，因此 insert() 方法可以返回插入记录的主键值
        $new_post = array('title' => 'new post');
        $new_post_id = $mysql->insert('post', $new_post);

        // 即便是自增类型的主键，也可以直接指定一个主键值
        $other_post = array('title' => 'new post', 'post_id' => $new_post_id + 1);
        $other_post_id = $mysql->insert('post', $other_post);

        // 可以使用 $alias 参数指定字段别名
        $alias = array('t' => 'title');
        $last_post = array('t' => 'last post');
        $last_post_id = $mysql->insert('post', $last_post, $alias);
        // #END EXAMPLE

        $this->assertType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $new_post_id);
        $this->assertType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT, $last_post_id);
        $this->assertTrue($new_post_id > 0, "\$new_post_id = {$new_post_id}");
        $this->assertEquals($new_post_id + 1, $other_post_id);
    }

    /**
     * 更新符合条件的记录
     *
     * @api MySQLAdapter::update()
     */
    function test_update()
    {
        $mysql = $this->_mysql;
        $currents = array();
        $mysql->find('post', 'post_id <= 10')->each(function ($record) use (& $currents) {
            $currents[$record['post_id']] = $record;
        });

        /**
         * #BEGIN EXAMPLE
         *
         * update() 方法的更新条件和 find_one()、find() 方法的查询条件写法一致。
         */
        // 更新 post_id = 1 的记录，将 title 更新为 post 1 updated
        $update = array('title' => 'post 1 updated');
        $result1 = $mysql->update('post', array('post_id' => 1), $update);

        // 更新所有 post_id <= 5 的记录，将这些记录的 click_count 字段值增加 100
        $update = 'click_count = click_count + 100';
        $result2 = $mysql->update('post', 'post_id <= 5', $update);

        // 更新所有 post_id > 5 AND post_id <= 10的记录，将 title 字段更新为 updated，
        // click_count 增加 50
        $update = array('title' => 'updated', 'click_count = click_count + 50');
        $result3 = $mysql->update('post', 'post_id > 5 AND post_id <= 10', $update);
        // #END EXAMPLE

        $this->assertEquals(1, $result1);
        $reload = $mysql->find_one('post', 'post_id = 1');
        $this->assertEquals('post 1 updated', $reload['title']);
        $this->assertEquals(5, $result2);
        $this->assertEquals(5, $result3);

        $new = $mysql->find('post', 'post_id <= 10')->fetch_all();
        foreach ($new as $record)
        {
            $post_id = $record['post_id'];
            if ($post_id <= 5)
            {
                $this->assertEquals($currents[$post_id]['click_count'] + 100, $record['click_count']);
            }
            else
            {
                $this->assertEquals($currents[$post_id]['click_count'] + 50, $record['click_count']);
                $this->assertEquals('updated', $record['title']);
            }
        }
    }

    /**
     * 删除符合条件的记录
     *
     * @api MySQLAdapter::del()
     */
    function test_del()
    {
        $mysql = $this->_mysql;

        /**
         * #BEGIN EXAMPLE
         *
         * del() 方法返回被删除的记录数。
         *
         * 删除条件的写法与 find_one()、find() 方法相同。
         */
        $result1 = $mysql->del('post', array('post_id' => 2));
        $result2 = $mysql->del('post', 'post_id >= 8 AND post_id <= 10');
        // #END EXAMPLE

        $this->assertEquals(1, $result1);
        $this->assertEquals(3, $result2);
    }

    function test_commit()
    {
        $this->_mysql->begin();
        $title = "new post " . mt_rand(1, 999);
        $new_post = array('title' => $title);
        $new_post_id = $this->_mysql->insert('post', $new_post);
        $this->_mysql->commit();

        $post = $this->_mysql->find_one('post', array('post_id' => $new_post_id));
        $this->assertType('array', $post);
        $this->assertArrayHasKey('post_id', $post);
        $this->assertArrayHasKey('title', $post);
        $this->assertEquals($new_post['title'], $post['title']);
        $this->assertEquals($new_post_id, $post['post_id']);
    }

    function test_rollback()
    {
        $this->_mysql->begin();
        $title = "new post " . mt_rand(1, 999);
        $new_post = array('title' => $title);
        $new_post_id = $this->_mysql->insert('post', $new_post);
        $this->_mysql->rollback();

        $post = $this->_mysql->find_one('post', array('post_id' => $new_post_id));
        $this->assertFalse($post);
    }

    function test_escape()
    {
        $this->assertEquals(123, $this->_mysql->escape(123));
        $this->assertEquals(123.5, $this->_mysql->escape(123.5));
        $this->assertEquals("'abc'", $this->_mysql->escape('abc'));
        $this->assertEquals('TRUE', $this->_mysql->escape(true));
        $this->assertEquals('FALSE', $this->_mysql->escape(false));
        $this->assertEquals('NULL', $this->_mysql->escape(null));
        $this->assertEquals("'abc\\'def'", $this->_mysql->escape('abc\'def'));
        $this->assertEquals("'abc\\\\def'", $this->_mysql->escape('abc\\def'));
        $this->assertEquals("'abc',123", $this->_mysql->escape(array('abc', 123)));
    }

    function test_id()
    {
        $this->assertEquals("`domain`", $this->_mysql->id('domain'));
        $this->assertEquals("`domain`.`collection`", $this->_mysql->id('domain.collection'));
        $this->assertEquals("`collection`.*", $this->_mysql->id('collection.*'));
        $this->assertEquals("*", $this->_mysql->id('*'));
    }

    protected function setup()
    {
        global $logger;

        $config = StorageFixture::set_default_mysql_domain_config();
        $this->_mysql = new MySQLAdapter($config);
        $this->_mysql->connect();
        $this->_mysql->set_logger(Logger::instance('test'));
        $this->_handle = $this->_mysql->handle();

        $this->tearDown();

        $this->_query('SET AUTOCOMMIT=0');
        $this->_query('START TRANSACTION');
        $this->_create_recordset('post', StorageFixture::post_recordset());
        $this->_query('COMMIT');
        $this->_query('SET AUTOCOMMIT=1');
    }

    protected function teardown()
    {
        $this->_query('DELETE FROM post');
    }

    private function _create_recordset($table, array $recordset)
    {
        $table = $this->_mysql->id($table);
        foreach ($recordset as $record)
        {
            foreach ($record as $key => $value)
            {
                $record[$this->_mysql->id($key)] = $this->_mysql->escape($value);
                unset($record[$key]);
            }

            $sql = "INSERT INTO {$table} ";
            $sql .= '(' . implode(', ', array_keys($record)) . ')';
            $sql .= ' VALUES (' . implode(', ', array_values($record)) . ')';
            $this->_query($sql);
        }
    }

    private function _query($sql)
    {
        $result = mysql_query($sql, $this->_handle);
        if ($result === false)
        {
            throw new \Exception(mysql_error($this->_handle), mysql_errno($this->_handle));
        }
        return $result;
    }

}

