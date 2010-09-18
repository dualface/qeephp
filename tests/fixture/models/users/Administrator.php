<?php

namespace tests\fixture\models\users;

/**
 * @bind class: tests\fixture\models\users\AdministratorPlugin
 *       arg1: 1
 *
 * @bind class: tests\fixture\models\users\EmptyPlugin
 */
class Administrator extends BaseUser
{
    /**
     * @var int
     * @update incr
     */
    public $login_count = 0;

}

