<?php

namespace tests\cases\storage;

use tests\includes\TestCase;
use tests\fixture\models\Post;

use qeephp\Event;
use qeephp\storage\IModel;
use qeephp\storage\BaseModel;

require_once __DIR__ . '/__init.php';

class ModelEventsTest extends ModelTestHelper
{
    //    /* 事件 */
    //    /* class events */
    //    const BEFORE_FIND_EVENT     = '__before_find';
    //    const AFTER_FIND_EVENT      = '__after_find';
    //    /* instance events */
    //    const AFTER_READ_EVENT      = '__after_read';
    //    const BEFORE_SAVE_EVENT     = '__before_save';
    //    const AFTER_SAVE_EVENT      = '__after_save';
    //    const BEFORE_CREATE_EVENT   = '__before_create';
    //    const AFTER_CREATE_EVENT    = '__after_create';
    //    const BEFORE_UPDATE_EVENT   = '__before_update';
    //    const AFTER_UPDATE_EVENT    = '__after_update';
    //    const BEFORE_DEL_EVENT      = '__before_del';
    //    const AFTER_DEL_EVENT       = '__after_del';

    function test_find_event()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * BEFORE_FIND_EVENT 和 AFTER_FIND_EVENT 事件
         *
         * @api BaseModel::find_one()
         * @api BaseModel::find_multi()
         * @api Repo::find_one()
         * @api Repo::find_multi()
         *
         * 只有 find_one() 和 find_multi() 方法会触发事件，find() 方法则不会触发事件。
         *
         * BEFORE_FIND_EVENT 会接受3个参数：
         * -  Event 对象
         * -  查询参数
         * -  查询方式*
         *
         * AFTER_FIND_EVENT 的响应方法会接受5个参数：
         * -  Event 对象
         * -  查询参数
         * -  查询方式*
         * -  查询得到的对象
         * -  查询得到的对象记录
         *
         * 查询方式参数指示是哪一个查询方法触发了查询事件，可能的值为：
         * -  find_one
         * -  find_multi
         */
        $check = array('before' => 0, 'after' => 0, 'id_list' => array());
        $before = function (Event $event, $cond, $mode) use (& $check) {
            // 记录事件触发的次数
            $check['before']++;
        };
        $after = function (Event $event, $cond, $mode, array $models) use (& $check) {
            $check['after']++;
            // 记录所有查询到的对象的ID，以及这些对象被查询的次数
            foreach ($models as $model)
            {
                $id = $model->id();
                if (!isset($check['id_list'][$id])) $check['id_list'][$id] = 0;
                $check['id_list'][$id]++;
            }
        };

        // 注册事件响应方法
        $meta = Post::meta();
        $meta->add_event_listener(IModel::BEFORE_FIND_EVENT, $before);
        $meta->add_event_listener(IModel::AFTER_FIND_EVENT, $after);

        // 对同一个对象查询两次，会触发两次事件，事件处理方法的 $mode 参数值为 find_one
        $post1 = Post::find_one(1);
        $post1_again = Post::find_one(1);
        
        // 查询另一个对象
        $post2 = Post::find_one(array('postId' => 2));

        // 查询多个对象，事件处理方法的 $mode 参数值为 find_multi
        $posts = Post::find_multi(array(1, 2, 3, 4));

        /**
         * 上述查询执行完毕后，$check 的内容如下：
         *
         * Array
         * (
         *     [before] => 4
         *     [after]  => 4
         *     [id_list] => Array
         *         (
         *             [1] => 3
         *             [2] => 2
         *             [3] => 1
         *             [4] => 1
         *         )
         * )
         */

        // 取消注册事件响应方法
        $meta->remove_event_listener(IModel::BEFORE_FIND_EVENT, $before);
        $meta->remove_event_listener(IModel::AFTER_FIND_EVENT, $after);
        // #END EXAMPLE

        $this->assertEquals(4, $check['before']);
        $this->assertEquals(4, $check['after']);
        $this->assertEquals(4, count($check['id_list']));
        $expected = array(1 => 3, 2 => 2, 3 => 1, 4 => 1);
        for ($i = 1; $i <= count($expected); $i++)
        {
            $this->assertEquals($expected[$i], $check['id_list'][$i]);
        }
    }

    function test_after_read_event()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * AFTER_READ 事件
         *
         * @api BaseModel::find_one()
         * @api BaseModel::find_multi()
         * @api BaseModel::find()
         * @api Repo::find_one()
         * @api Repo::find_multi()
         * @api Repo::find()
         *
         * 当对象从存储中读取出来后，会触发对象的 AFTER_READ_EVENT 事件。
         * AFTER_READ_EVENT 事件比 AFTER_FIND_EVENT 事件先触发。
         */
        $check = array('id_list' => array());
        $handler = function (Event $event, BaseModel $model) use (& $check) {
            // 记录所有查询到的对象的ID，以及这些对象被查询的次数
            $id = $model->id();
            if (!isset($check['id_list'][$id])) $check['id_list'][$id] = 0;
            $check['id_list'][$id]++;
        };
        Post::meta()->add_event_listener(IModel::AFTER_READ_EVENT, $handler);

        $post = Post::find_one(1);
        $post2 = Post::find_one(2);
        $posts = Post::find_multi(array(1, 2, 3));
        $more  = Post::find(array('[postId] <= ?', 5))->fetch_all();

        /**
         * 上述查询执行完毕后，$check 的内容如下：
         *
         * Array
         * (
         *     [id_list] => Array
         *         (
         *             [1] => 3
         *             [2] => 3
         *             [3] => 2
         *             [4] => 1
         *             [5] => 1
         *         )
         * )
         */

        Post::meta()->remove_event_listener(IModel::AFTER_READ_EVENT, $handler);
        // #END EXAMPLE

        $this->assertEquals(5, count($check['id_list']));
        $expected = array(1 => 3, 2 => 3, 3 => 2, 4 => 1, 5 => 1);
        for ($i = 1; $i <= count($expected); $i++)
        {
            $this->assertEquals($expected[$i], $check['id_list'][$i]);
        }
    }

    function test_create_event()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * BEFORE_SAVE_EVENT、AFTER_SAVE_EVENT、BEFORE_CREATE_EVENT 和 AFTER_CREATE_EVENT 事件
         *
         * @api BaseModel::save()
         * @api Repo::save()
         * @api Repo::create()
         *
         * 当对象的 is_fresh() 方法返回 true 时，调用 save() 方法会在存储中创建对象。
         *
         * save() 方法会依次触发下列事件：
         * -  BEFORE_SAVE_EVENT
         * -  BEFORE_CREATE_EVENT
         * -  AFTER_CREATE_EVENT
         * -  AFTER_SAVE_EVENT
         */
        $check = array();
        $handler = function (Event $event, BaseModel $model) use (& $check) {
            $check[] = $event->name;
        };

        $meta = Post::meta();
        $meta->add_event_listener(IModel::BEFORE_SAVE_EVENT, $handler);
        $meta->add_event_listener(IModel::BEFORE_CREATE_EVENT, $handler);
        $meta->add_event_listener(IModel::AFTER_CREATE_EVENT, $handler);
        $meta->add_event_listener(IModel::AFTER_SAVE_EVENT, $handler);

        $post = new Post();
        $post->author = 'new author';
        $post->click_count = 0;
        $post->title = 'new title random';
        $id = $post->save();

        /**
         * 上述代码执行完毕后，$check 的内容如下：
         *
         * Array
         * (
         *     [0] => __before_save
         *     [1] => __before_create
         *     [2] => __after_create
         *     [3] => __after_save
         * )
         */

        $meta->remove_event_listener(IModel::BEFORE_SAVE_EVENT, $handler);
        $meta->remove_event_listener(IModel::BEFORE_CREATE_EVENT, $handler);
        $meta->remove_event_listener(IModel::AFTER_CREATE_EVENT, $handler);
        $meta->remove_event_listener(IModel::AFTER_SAVE_EVENT, $handler);
        // #END EXAMPLE

        $this->assertEquals($post->id(), $id);
        $expected = array(
            IModel::BEFORE_SAVE_EVENT,
            IModel::BEFORE_CREATE_EVENT,
            IModel::AFTER_CREATE_EVENT,
            IModel::AFTER_SAVE_EVENT
        );
        $this->assertEquals($expected, $check);
    }

    function test_update_event()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * BEFORE_SAVE_EVENT、AFTER_SAVE_EVENT、BEFORE_UPDATE_EVENT 和 AFTER_UPDATE_EVENT 事件
         *
         * @api BaseModel::save()
         * @api Repo::save()
         * @api Repo::update()
         *
         * 当对象的 is_fresh() 方法返回 false 时，调用 save() 方法会更新存储中的对象。
         *
         * save() 方法会依次触发下列事件：
         * -  BEFORE_SAVE_EVENT
         * -  BEFORE_UPDATE_EVENT
         * -  AFTER_UPDATE_EVENT
         * -  AFTER_SAVE_EVENT
         */
        $check = array();
        $handler = function (Event $event, BaseModel $model) use (& $check) {
            $check[] = $event->name;
        };

        $meta = Post::meta();
        $meta->add_event_listener(IModel::BEFORE_SAVE_EVENT, $handler);
        $meta->add_event_listener(IModel::BEFORE_UPDATE_EVENT, $handler);
        $meta->add_event_listener(IModel::AFTER_UPDATE_EVENT, $handler);
        $meta->add_event_listener(IModel::AFTER_SAVE_EVENT, $handler);

        $post = Post::find_one(1);
        $post->title = strrev($post->title);
        $is_true = $post->save();

        /**
         * 上述代码执行完毕后，$check 的内容如下：
         *
         * Array
         * (
         *     [0] => __before_save
         *     [1] => __before_update
         *     [2] => __after_update
         *     [3] => __after_save
         * )
         */

        $meta->remove_event_listener(IModel::BEFORE_SAVE_EVENT, $handler);
        $meta->remove_event_listener(IModel::BEFORE_UPDATE_EVENT, $handler);
        $meta->remove_event_listener(IModel::AFTER_UPDATE_EVENT, $handler);
        $meta->remove_event_listener(IModel::AFTER_SAVE_EVENT, $handler);
        // #END EXAMPLE

        $this->assertTrue($is_true);
        $expected = array(
            IModel::BEFORE_SAVE_EVENT,
            IModel::BEFORE_UPDATE_EVENT,
            IModel::AFTER_UPDATE_EVENT,
            IModel::AFTER_SAVE_EVENT
        );
        $this->assertEquals($expected, $check);
    }

    function test_del_event()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * BEFORE_DEL_EVENT 和 AFTER_DEL_EVENT 事件
         *
         * @api BaseModel::del()
         * @api BaseModel::del_one()
         * @api BaseModel::del_by()
         * @api Repo::del()
         * @api Repo::del_by()
         *
         * 当对象的 is_fresh() 方法返回 false 时，调用 del() 方法会删除存储中的对象。
         */
        $check = array(
            IModel::BEFORE_DEL_EVENT => array(),
            IModel::AFTER_DEL_EVENT => array()
        );
        $handler = function (Event $event, BaseModel $model) use (& $check) {
            // 记录被删除的对象的 ID
            $check[$event->name][] = $model->id();
        };

        $meta = Post::meta();
        $meta->add_event_listener(IModel::BEFORE_DEL_EVENT, $handler);
        $meta->add_event_listener(IModel::AFTER_DEL_EVENT, $handler);

        Post::find_one(1)->del();
        Post::del_one(2);
        Post::del_by(array('[postId] > ? AND [postId] < ?', 5, 8));

        /**
         * 上述代码执行完毕后，$check 的内容如下：
         *
         * Array
         * (
         *     [__before_del] => Array
         *         (
         *             [0] => 1
         *             [1] => 2
         *             [2] => 6
         *             [3] => 7
         *         )
         *
         *     [__after_del] => Array
         *         (
         *             [0] => 1
         *             [1] => 2
         *             [2] => 6
         *             [3] => 7
         *         )
         * )
         */

        $meta->remove_event_listener(IModel::BEFORE_DEL_EVENT, $handler);
        $meta->remove_event_listener(IModel::AFTER_DEL_EVENT, $handler);
        // #END EXAMPLE

        $expected = array(
            IModel::BEFORE_DEL_EVENT => array(1, 2, 6, 7),
            IModel::AFTER_DEL_EVENT => array(1, 2, 6, 7)
        );
        $this->assertEquals($expected, $check);

        $this->assertFalse($this->_get_post_record(1));
        $this->assertFalse($this->_get_post_record(2));
        $this->assertFalse($this->_get_post_record(6));
        $this->assertFalse($this->_get_post_record(7));
    }
}
