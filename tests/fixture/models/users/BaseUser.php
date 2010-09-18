<?php

namespace tests\qeephp\fixture\models\users;

use qeephp\storage\BaseModel;

/**
 * @collection user
 *
 * @extends by: cls
 *          classes: Guest=0, Member=1, Administrator=2
 */
abstract class BaseUser extends BaseModel
{
    /**
     * @var serial
     */
    public $id;

    /**
     * @var string(20)
     * @index unique
     */
    public $username;

    /**
     * @var string(80)
     */
    public $password;
}

