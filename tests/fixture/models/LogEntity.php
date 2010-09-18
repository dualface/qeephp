<?php

namespace tests\fixture\models;

use qeephp\storage\BaseModel;

/**
 * @collection log_entity
 */
class LogEntity extends BaseModel
{
    /**
     * 主键
     *
     * @var int
     * @id
     */
    public $log_id;

    /**
     *
     * @var int
     * @id
     */
    public $user_id;
}

