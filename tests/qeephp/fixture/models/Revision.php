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
     * 主键
     *
     * @var int
     * @id autoincr
     */
    public $rev_id;

    /**
     *
     * @var int
     * @field post_id
     * @id
     */
    public $postId;
}

