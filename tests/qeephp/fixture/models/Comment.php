<?php

namespace tests\qeephp\fixture\models;

use qeephp\storage\BaseModel;

/**
 * @collection comment
 *
 * @readonly
 * @update all | check_changed
 */
class Comment extends BaseModel
{
    const TEST_COLLECTION = 'comment';
    const TEST_UPDATE = 0x0009; // Meta::UPDATE_ALL_PROPS | Meta::UPDATE_CHECK_CHANGED;
    const TEST_READONLY = true;
    const TEST_IDNAME = 'comment_id';
    const TEST_AUTOINCR_IDNAME = 'comment_id';
    const TEST_PROP_AUTHOR_DEFAULT = 'guest';

    /**
     * 主键
     *
     * @var int
     * @id autoincr
     */
    public $comment_id;

    /**
     * 外键
     *
     * @var int
     */
    public $post_id;

    /**
     * 评论内容
     *
     * @var text
     */
    public $body;

    /**
     * 评论人
     *
     * @var string
     * @optional
     */
    public $author = self::TEST_PROP_AUTHOR_DEFAULT;

    /**
     * 评论创建时间
     *
     * @var int
     * @readonly
     */
    public $created;

    /**
     * 评论创建时间的文本表示方式
     *
     * @var string
     * @getter get_created_string
     * @nonp
     */
    public $created_string;

    function get_created_string()
    {
        return date('Y-m-d H:i:s', $this->created);
    }

}

