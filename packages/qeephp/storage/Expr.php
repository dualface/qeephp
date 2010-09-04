<?php

namespace qeephp\storage;

class Expr
{
    /**
     * @var array
     */
    public $expr;

    function __construct($expr)
    {
        $this->expr = func_get_args();
    }
}

