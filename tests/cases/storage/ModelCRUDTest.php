<?php

namespace tests\cases\storage;

use tests\fixture\StorageFixture;
use tests\fixture\models\Post;
use tests\fixture\models\Comment;
use tests\fixture\models\Revision;

use qeephp\storage\Repo;

require_once __DIR__ . '/__init.php';

class ModelCRUDTest extends ModelTestHelper
{
    function test_find_one()
    {
        $class = 'tests\\fixture\\models\\Post';

        /**
         * #BEGIN EXAMPLE
         *
         * 查询指定主键值的对象
         *
         * @api Repo::find_one()
         * @api BaseModel::find_one()
         *
         * find_one() 严格按照主键值查询指定的对象。
         *
         * 提示：BaseModel::find_one() 调用 Repo::find_one() 进行查询操作。
         */
        $post5 = Post::find_one(5);
        $post6 = Post::find_one(array('postId' => 6));
        // #END EXAMPLE
        
        $this->_check_post($post5, 5);
        $this->_check_post($post6, 6);
    }

    function test_find_multi()
    {
        $post_class = 'tests\\fixture\\models\\Post';

        /**
         * #BEGIN EXAMPLE
         *
         * 查询指定主键值的多个对象
         *
         * @api Repo::find_multi()
         * @api BaseModel::find_multi()
         *
         * find_multi() 只支持使用一个主键的对象。$id_list 参数只能是包含多个主键值的数组。
         *
         * 提示：BaseModel::find_multi() 调用 Repo::find_multi() 进行查询操作。
         */
        $post_id_list = array(1, 3, 5);
        $posts = Post::find_multi($post_id_list);
        // #END EXAMPLE

        $this->assertEquals(3, count($posts));
        $id = 1;
        foreach ($posts as $post_id => $post)
        {
            $this->_check_post($post, $post_id);
            $this->assertEquals($id, $post_id);
            $id += 2;
        }
    }

    function test_find()
    {
        $post_class = 'tests\\fixture\\models\\Post';
        /**
         * #BEGIN EXAMPLE
         * 
         * 按照任意条件查询对象
         *
         * @api Repo::find()
         * @api BaseModel::find()
         *
         * find() 方法的 $cond 参数和 IAdapter::find() 方法相同，可以使用各种类型的查询条件。
         *
         * 提示：BaseModel::find() 调用 Repo::find() 进行查询操作。
         */
        $posts = Post::find(array('post_id > ? AND post_id < ?', 1, 5))->fetch_all();
        // #END EXAMPLE

        $this->assertType('array', $posts);
        foreach($posts as $post)
        {
            $this->assertType($post_class, $post);
            $this->_check_post($post, $post->id());
        }
    }

    function test_create()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 保存新创建的对象
         *
         * @api Repo::save();
         * @api Repo::create()
         * @api BaseModel::save()
         *
         * 使用 new 构造的对象实例，在调用 Repo::save() 保存时会自动调用 Repo::create() 方法。
         *
         * 在创建新对象时，create() 和 save() 方法会返回新对象的主键值。
         *
         * 提示：BaseModel::save() 调用 Repo::save() 进行对象的存储操作。
         */
        $post_id = 99;
        $post = new Post();
        $post->postId = $post_id;
        $post->title  = 'post 99';
        $post->author = 'author 99';
        $post->click_count = 99;
        $id = $post->save();
        // # END EXAMPLE

        $this->assertEquals($post_id, $id);
        $record = $this->_get_post_record(99);
        $this->assertType('array', $record);
        $this->assertEquals(99, $record['post_id']);
        $this->assertEquals('post 99', $record['title']);
        $this->assertEquals('author 99', $record['author']);
        $this->assertEquals(99, $record['click_count']);

        /**
         * #BEGIN EXAMPLE
         *
         * 在创建对象时使用自增字段
         *
         * @api Repo::save();
         * @api Repo::create()
         * @api BaseModel::save()
         *
         * 如果对象的存储（例如 MySQL）使用了自增字段，那么在调用 save() 前不用为该字段对应的属性指定值。
         * save() 成功保存对象后，会确保对象的 id() 方法返回为该对象自动分配的主键值。
         */
        $comment = new Comment();
        $comment->post_id = $post_id;
        $comment->created = time();
        $comment->author  = 'dualface';
        $comment->body    = 'new comment';
        $id = $comment->save();
        // #END EXAMPLE

        $this->assertType('int', $id);
        $this->assertEquals($id, $comment->id());
        $this->assertEquals($id, $comment->comment_id);

        /**
         * #BEGIN EXAMPLE
         *
         * 在创建对象时使用复合主键
         *
         * @api Repo::save();
         * @api Repo::create()
         * @api BaseModel::save()
         *
         * 如果使用了复合主键，则需要提供所有主键值。但如果某个主键是自增字段，则可以不提供该主键的值。
         *
         * 使用复合主键时，create() 和 save() 方法返回包含所有主键值的数组。
         */
        // Revision 对象的 rev_id 主键是自增资段，而 post_id 则是非自增主键。
        $rev = new Revision();
        $rev->postId  = 1;
        $rev->created = time();
        $rev->body    = 'post 1 rev';
        $id = $rev->save();
        // #END EXAMPLE

        $this->assertType('array', $id);
        $this->assertArrayHasKey('rev_id', $id);
        $this->assertArrayHasKey('postId', $id);
        $obj_id = $rev->id();
        ksort($obj_id, SORT_ASC);
        ksort($id, SORT_ASC);
        $this->assertEquals($obj_id, $id);
    }

    function test_simple_update()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 保存对象的改动
         *
         * @api Repo::save()
         * @api Repo::update()
         * @api BaseModel::save()
         *
         * 使用 find* 方法读取出来的对象，调用 save() 可将对象的改动保存起来。
         * 如果确实更新了存储的数据，则 save() 方法返回 true，否则返回 false。
         *
         * 提示：BaseModel::save() 调用 Repo::save() 进行对象的存储操作。
         */
        $post = Post::find_one(1);
        $post->title = strrev($post->title);
        $success = $post->save();
        // #END EXAMPLE

        $this->assertTrue($success);
        $another_post = Post::find_one(1);
        $this->assertFalse($post === $another_post);
        $this->assertEquals($post->title,  $another_post->title);
        $this->assertEquals($post->postId, $another_post->postId);
    }

    function test_update_changed_props()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 仅保存对象改动过的属性
         *
         * @api Repo::save()
         * @api Repo::update()
         * @api BaseModel::save()
         *
         * 默认情况下，保存改动后的对象到存储时，会保存对象的所有属性，即便只有部分属性发生了改变。
         * 关于更新策略的详细说明，参考 Meta 类的文档。
         *
         * 如果要在更新时只保存改动过的属性，则应该在模型类使用 @update 标注指定更新策略。
         */
        // 对同一对象读取两次，模拟并发的请求
        $post = Post::find_one(1);
        $post2 = Post::find_one(1);

        // 分别修改两个实例的不同属性，并保存
        $post->title = 'new post 1';
        $post2->author = 'new post 1 author';
        $post->save();
        $post2->save();

        // 重新读取对象，可以看到 title 和 author 属性都发生了变化
        $post3 = Post::find_one(1);
        $is_title_euqals = ($post3->title == $post->title);
        $is_author_equals = ($post3->author == $post2->author);
        // #END EXAMPLE

        $this->assertTrue($is_title_euqals);
        $this->assertTrue($is_author_equals);
    }

    function test_update_check_changed_props()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 更新时检查改动过的属性，避免并发冲突
         *
         * @api Repo::save()
         * @api Repo::update()
         * @api BaseModel::save()
         *
         * 合理设置模型类的 @update 标注，可以一定程度上避免并发更新冲突。
         *
         * 例如 Post 类的 @update 设置为 changed, check_changed。
         * 则在保存改动过的 Post 对象时，仅会保存改动过的属性。
         * 并对改动改动过的属性进行检查，确保存储中该属性的值没有发生变化。
         *
         * 有关 @update 的详细设定，请参考 Meta 文档。
         */
        // 对同一对象读取两次，模拟并发的请求
        $post = Post::find_one(1);
        $post2 = Post::find_one(1);

        // 更改两个对象的 title 属性
        $post->title = 'changed post 1';
        $post2->title = 'changed post 1 again';

        // 保存时，后一次 save() 将会返回 false
        $is_true = $post->save();
        $is_false = $post2->save();
        // #END EXAMPLE

        $this->assertTrue($is_true);
        $this->assertFalse($is_false);
    }

    function test_update_prop_by_arithmetic()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 使用算术运算更新对象属性
         *
         * @api Repo::save()
         * @api Repo::update()
         * @api BaseModel::save()
         *
         * 为了尽可能减少并发更新冲突，对于有些更新可以采用算术运算。详细讨论请参考：
         * http://dualface.qeephp.com/index.php/archives/1042
         */
        // 读取对象的当前值
        $post_id = 1;
        $origin = Post::find_one($post_id);

        // 对同一对象读取两次，模拟并发的请求
        $post = Post::find_one($post_id);
        $post->click_count += 100;  // 在请求1中，click_count 增加了 100
        $post->title = strrev($post->title);

        $post2 = Post::find_one($post_id);
        $post2->click_count += 200; // 在请求2中，click_count 增加了 200

        // 请求1、2分别保存
        $result = $post->save();
        $result2 = $post2->save();
        
        // 重新读取对象，获得对象的新值
        $current = Post::find_one($post_id);
        // click_count 的当前值应该在原有基础上增加了 300
        $is_equals = ($origin->click_count + 300) == $current->click_count;
        // #END EXAMPLE

        $this->assertTrue($result);
        $this->assertTrue($result2);
        $this->assertTrue($is_equals);
    }

    function test_del_and_del_one()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 删除指定的一个对象
         *
         * @api Repo::del_one()
         * @api BaseModel::del()
         */
        // 查询指定主键值的对象并删除，成功返回 true
        $post = Post::find_one(1);
        $is_true = $post->del();

        // 另一种删除指定对象的方式，效果与 find_one(...)->del() 等同
        $is_true_too = Post::del_one(2);
        // #END EXAMPLE

        $this->assertTrue($is_true);
        $this->assertTrue($is_true_too);
        $this->assertFalse($this->_get_post_record(1));
        $this->assertFalse($this->_get_post_record(2));
    }

    function test_del_by()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 删除符合条件的对象
         *
         * @api Repo::del_by()
         * @api BaseModel::del_by()
         *
         * del_by() 先用 find() 查询出符合条件的对象，然后调用这些对象的 del() 方法。
         */
        // 删除 5 个对象
        $result = Post::del_by(array('post_id >= ? AND post_id <= ?', 1, 5));
        // #END EXAMPLE

        $this->assertEquals(5, $result);
    }

    function test_erase_one()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 从存储中删除指定的对象
         *
         * @api Repo::erase_one()
         * @api BaseModel::erase_one()
         *
         * erase_one() 和 del_one() 的作用类似，但有下列区别：
         *
         * -  erase_one() 直接从存储中删除对象，不需要先查询出要删除的对象
         * -  erase_one() 引发的事件和 del_one() 有区别
         */
        $post_id = 1;
        $is_true = Post::erase_one($post_id);
        // #END EXAMPLE

        $this->assertTrue($is_true);
        $this->assertFalse($this->_get_post_record(1));
    }

    function test_erase_by()
    {
        /**
         * #BEGIN EXAMPLE
         *
         * 直接从存储中删除符合条件的对象，返回被删除对象的总数
         *
         * @api BaseModel::erase_by()
         */
        // 直接删除 5 个对象
        $result = Post::erase_by(array('post_id >= ? AND post_id <= ?', 1, 5));
        // #END EXAMPLE

        $this->assertEquals(5, $result);
        $this->assertFalse($this->_get_post_record(1));
        $this->assertFalse($this->_get_post_record(2));
        $this->assertFalse($this->_get_post_record(3));
        $this->assertFalse($this->_get_post_record(4));
        $this->assertFalse($this->_get_post_record(5));
    }
}

