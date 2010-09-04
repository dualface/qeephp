<?php

namespace tests\qeephp\fixture\models\users;

/**
 * @bind class: tests\qeephp\fixture\models\users\AdministratorPlugin
 *       arg1: 1
 *
 * @bind class: tests\qeephp\fixture\models\users\EmptyPlugin
 */
class Administrator extends BaseUser
{
    /**
     * @var int
     * @update incr
     */
    public $login_count = 0;

}

