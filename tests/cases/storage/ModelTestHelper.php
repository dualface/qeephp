<?php

namespace tests\cases\storage;

use tests\includes\TestCase;
use tests\fixture\StorageFixture;
use tests\fixture\models\Post;
use tests\fixture\models\Comment;
use tests\fixture\models\Revision;

use qeephp\storage\Repo;
use qeephp\tools\Logger;

require_once dirname(__DIR__) . '/__init.php';

abstract class ModelTestHelper extends TestCase
{
    protected $_default_adapter;
    protected $_recordset;

    protected function setup()
    {
        StorageFixture::set_default_mysql_domain_config();
        StorageFixture::set_second_domain_config();
        $this->_cleanup();

        $this->_default_adapter = Repo::select_adapter(StorageFixture::DEFAULT_NODE);
        $this->_default_adapter->set_logger(Logger::instance('test'));
        $this->_create_posts();
    }

    protected function teardown()
    {
        $this->_cleanup();
    }

    protected function _cleanup()
    {
        $adapter = Repo::select_adapter(Post::meta()->domain());
        $adapter->del('post', null);
        $adapter->del('comment', null);
        $this->_default_adapter = null;
        $this->_recordset = null;
    }

    protected function _create_recordset($collection, array $recordset, $idname = null)
    {
        foreach ($recordset as $offset => $record)
        {
            $result = $this->_default_adapter->insert($collection, $record);
            if ($idname)
            {
                $recordset[$offset][$idname] = $result;
            }
        }
        return $recordset;
    }

    protected function _create_posts()
    {
        $this->_recordset = StorageFixture::post_recordset();
        $this->_create_recordset(Post::meta()->collection(), $this->_recordset);
    }

    protected function _create_revisions()
    {
        $recordset = StorageFixture::revisions_recordset();
        $meta = Revision::meta();
        return $this->_create_recordset($meta->collection(), $recordset, $meta->autoincr_idname);
    }

    protected function _check_post($post, $post_id)
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

    protected function _get_post_record($post_id)
    {
        $meta = Post::meta();
        $cond = array($meta->props_to_fields[$meta->idname] => $post_id);
        return $this->_default_adapter->find_one($meta->collection(), $cond);
    }
}
