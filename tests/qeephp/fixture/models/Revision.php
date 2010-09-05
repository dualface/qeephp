<?php

namespace tests\qeephp\fixture\models;

use qeephp\storage\BaseModel;

/**
 * @collection revision
 *
 * @update all | check_non
 */
class Revision extends BaseModel
{
    /**
     *
     * @var int
     * @field post_id
     * @id
     */
    public $postId;

    /**
     * 主键
     *
     * @var int
     * @id autoincr
     */
    public $rev_id;

    /**
     * @var text
     */
    public $body;

    /**
     * @var int
     */
    public $created;
}

