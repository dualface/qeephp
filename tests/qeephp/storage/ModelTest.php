<?php

namespace tests\qeephp\storage;

use qeephp\errors\ModelError;
use qeephp\storage\Meta;
use qeephp\storage\UnitOfWork;

use tests\qeephp\fixture\models\Post;
use tests\qeephp\fixture\ModelTestApp;

require_once __DIR__ . '/__init.php';

class ModelTest extends DbTest
{
    const MODEL_TYPE = 'qeephp\\interfaces\\Meta';
    const POST_TYPE = 'tests\\qeephp\\fixture\\models\\Post';
    
    /**
     * @var ModelTestApp
     */
    private $_app;

    function testNewModelInstance()
    {
        $post = new Post();
        $this->assertType(self::MODEL_TYPE, $post);
    }

    function testFindOne()
    {
        $post = Post::one(1);
        /* @var $post Post */
        $this->assertType(self::POST_TYPE, $post);
        $this->assertEquals(1, $post->postId);
        $this->assertEquals(1, $post->id());
        $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_FIND_ONE_EVENT),
                          'Post::isEventTriggered(Meta::BEFORE_FIND_ONE_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::AFTER_FIND_ONE_EVENT),
                          'Post::isEventTriggered(Meta::AFTER_FIND_ONE_EVENT)');
        $this->assertTrue(Post::isEventTriggered('model.beforeFindOne'),
                          "Post::isEventTriggered('model.beforeFindOne')");
        $this->assertTrue(Post::isEventTriggered('model.afterFind'),
                          "Post::isEventTriggered('model.afterFind')");
    }

    function testFindMulti()
    {
        $posts = Post::multi(array(1, 3, 5, 7));
        $this->assertEquals(4, count($posts));
        foreach ($posts as $postId => $post)
        {
            /* @var $post Post */
            $this->assertType(self::POST_TYPE, $post);
            $this->assertEquals($postId, $post->postId);
            $this->assertTrue($post->postId >= 1 && $post->postId <= 7);
        }
        $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_FIND_MULTI_EVENT),
                          'Post::isEventTriggered(Meta::BEFORE_FIND_MULTI_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::AFTER_FIND_MULTI_EVENT),
                          'Post::isEventTriggered(Meta::AFTER_FIND_MULTI_EVENT)');
        $this->assertTrue(Post::isEventTriggered('model.beforeFindMulti'),
                          "Post::isEventTriggered('model.beforeFindMulti')");
        $this->assertTrue(Post::isEventTriggered('model.afterFind'),
                          "Post::isEventTriggered('model.afterFind')");
    }

    function testUpdate()
    {
        $post = Post::one(1);
        /* @var $post Post */
        $this->assertType(self::POST_TYPE, $post);
        $post->title = 'new title 1';
        $post->save();

        $reload = Post::one(1);
        $this->assertEquals($post->title, $reload->title);
        $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_SAVE_EVENT),
                          'Post::isEventTriggered(Meta::BEFORE_SAVE_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::AFTER_SAVE_EVENT),
                          'Post::isEventTriggered(Meta::AFTER_SAVE_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_UPDATE_EVENT),
                          'Post::isEventTriggered(Meta::BEFORE_UPDATE_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::AFTER_UPDATE_EVENT),
                          'Post::isEventTriggered(Meta::AFTER_UPDATE_EVENT)');
        $this->assertTrue(Post::isEventTriggered('model.beforeSave'),
                          "Post::isEventTriggered('model.beforeSave')");
        $this->assertTrue(Post::isEventTriggered('model.afterSave'),
                          "Post::isEventTriggered('model.afterSave')");
        $this->assertTrue(Post::isEventTriggered('model.beforeUpdate'),
                          "Post::isEventTriggered('model.beforeUpdate')");
        $this->assertTrue(Post::isEventTriggered('model.afterUpdate'),
                          "Post::isEventTriggered('model.afterUpdate')");
    }

    function testCreate()
    {
        $post = new Post();
        $post->title = 'new title 199';
        $newPostId = $post->save();
        $reload = Post::one($newPostId);
        $this->assertEquals($post->title, $reload->title);
        $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_SAVE_EVENT),
                          'Post::isEventTriggered(Meta::BEFORE_SAVE_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::AFTER_SAVE_EVENT),
                          'Post::isEventTriggered(Meta::AFTER_SAVE_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_CREATE_EVENT),
                          'Post::isEventTriggered(Meta::BEFORE_CREATE_EVENT)');
        $this->assertTrue(Post::isEventTriggered(Meta::AFTER_CREATE_EVENT),
                          'Post::isEventTriggered(Meta::AFTER_CREATE_EVENT)');
        $this->assertTrue(Post::isEventTriggered('model.beforeSave'),
                          "Post::isEventTriggered('model.beforeSave')");
        $this->assertTrue(Post::isEventTriggered('model.afterSave'),
                          "Post::isEventTriggered('model.afterSave')");
        $this->assertTrue(Post::isEventTriggered('model.beforeCreate'),
                          "Post::isEventTriggered('model.beforeCreate')");
        $this->assertTrue(Post::isEventTriggered('model.afterCreate'),
                          "Post::isEventTriggered('model.afterCreate')");
    }

    function testDel()
    {
        $post = Post::one(1);
        /* @var $post Post */
        $post->del();

        try
        {
            $reload = Post::one(1);
            $this->assertFalse($reload);
        }
        catch (ModelError $ex)
        {
            $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_DEL_EVENT),
                              'Post::isEventTriggered(Meta::BEFORE_DEL_EVENT)');
            $this->assertTrue(Post::isEventTriggered(Meta::AFTER_DEL_EVENT),
                              'Post::isEventTriggered(Meta::AFTER_DEL_EVENT)');
            $this->assertTrue(Post::isEventTriggered('model.beforeDel'),
                              "Post::isEventTriggered('model.beforeDel')");
            $this->assertTrue(Post::isEventTriggered('model.afterDel'),
                              "Post::isEventTriggered('model.afterDel')");
            $this->assertEquals(ModelError::ENTITY_NOT_FOUND, $ex->getCode(),
                                'ModelError::ENTITY_NOT_FOUND == $ex->getCode()');
        }
    }

    function testErase()
    {
        Post::erase(1);
        try
        {
            $reload = Post::one(1);
        }
        catch (ModelError $ex)
        {
            $this->assertTrue(Post::isEventTriggered(Meta::BEFORE_ERASE_EVENT),
                              'Post::isEventTriggered(Meta::BEFORE_ERASE_EVENT)');
            $this->assertTrue(Post::isEventTriggered(Meta::AFTER_ERASE_EVENT),
                              'Post::isEventTriggered(Meta::AFTER_ERASE_EVENT)');
            $this->assertTrue(Post::isEventTriggered('model.beforeErase'),
                              "Post::isEventTriggered('model.beforeErase')");
            $this->assertTrue(Post::isEventTriggered('model.afterErase'),
                              "Post::isEventTriggered('model.afterErase')");
            $this->assertEquals(ModelError::ENTITY_NOT_FOUND, $ex->getCode(),
                                'ModelError::ENTITY_NOT_FOUND == $ex->getCode()');
        }
    }

    function testRevert()
    {
        $post = Post::one(1);
        /* @var $post Post */
        $currentTitle = $post->title;
        $currentValue = $post->value;

        $post->savePropValues();
        $post->title .= ' changed';
        $post->value++;
        $post->revertToSavedPropValues();

        $this->assertEquals($currentTitle, $post->title, 'Prop "title" not reverted.');
        $this->assertEquals($currentValue, $post->value, 'Prop "value" not reverted.');
    }

    function testUnitOfWork()
    {
        $post = Post::one(1);
        /* @var $post Post */
        $currentTitle = $post->title;
        $currentValue = $post->value;

        $unit = new UnitOfWork();
        $unit->add($post);

        $post->title .= ' changed';
        $post->value++;
        $post->postId++; // update will failed.

        $result = $unit->save();
        $this->assertFalse($result, '$unit save will failed.');

        $this->assertEquals($currentTitle, $post->title, 'Prop "title" not reverted.');
        $this->assertEquals($currentValue, $post->value, 'Prop "value" not reverted.');
    }

    protected function setUp()
    {
        $this->_app = new ModelTestApp();
        $this->_setUpMultiDbTest();
        $this->_multiDb->selectDomain('qeephp_tests_db1');
    }

    protected function tearDown()
    {
        $this->_tearDownMultiDbTest();
    }
}

