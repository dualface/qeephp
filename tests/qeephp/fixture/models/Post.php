<?php

namespace tests\qeephp\fixture\models;

use qeephp\storage\Meta;
use qeephp\storage\BaseModel;
use tests\qeephp\fixture\StorageFixture;

/**
 * @collection post
 *
 * @update changed | check_changed
 */
class Post extends BaseModel
{
    const TEST_DOMAIN = StorageFixture::DEFAULT_NODE;
    const TEST_COLLECTION = 'post';
    const TEST_UPDATE = 0x000a; // Meta::UPDATE_CHANGED_PROPS | Meta::UPDATE_CHECK_CHANGED;
    const TEST_READONLY = false;
    const TEST_IDNAME = 'postId';
    const TEST_AUTOINCR_IDNAME = null;

    const TEST_PROP_TITLE_TYPE = Meta::TYPE_STRING;
    const TEST_PROP_TITLE_LEN = 80;

    const TEST_PROP_CLICK_COUNT_UPDATE = Meta::UPDATE_PROP_INCR;

    /**
     * 主键
     *
     * @var int
     * @id
     * @field post_id
     */
    public $postId;

    /**
     * 标题
     *
     * @var string(80)
     */
    public $title;

    /**
     * 作者
     *
     * @var string(20)
     */
    public $author;

    /**
     * 值
     *
     * @var int
     * @nonp
     */
    public $value;

    /**
     * 累计点击次数
     *
     * @var int
     * @update incr
     */
    public $click_count = 0;

    /**
     * @var int
     * @internal
     */
    public $__admin_uid;
}

