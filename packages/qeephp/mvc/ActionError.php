<?php

namespace qeephp\mvc;

use qeephp\BaseError;

/**
 * 与 MVC 有关的异常
 */
class ActionError extends BaseError
{
    const NOT_SET_TOOL      = 1;
    const ACTION_NOT_FOUND  = 2;

    static function not_set_tool_error($toolname)
    {
        return new ActionError("NOT_SET_TOOL: {$toolname}", self::NOT_SET_TOOL);
    }

    static function action_not_found_error($action_name)
    {
        return new ActionError("ACTION_NOT_FOUND: {$action_name}", self::ACTION_NOT_FOUND);
    }
}

