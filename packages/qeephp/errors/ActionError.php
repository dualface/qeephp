<?php

namespace qeephp\errors;

/**
 * 与 MVC 有关的异常
 */
class MvcError extends BaseError
{
    const NOT_SET_TOOL      = 1;
    const ACTION_NOT_FOUND  = 2;

    /**
     * 没有设定工具
     *
     * @param string $toolname
     *
     * @return MvcError
     */
    static function not_set_tool_error($toolname)
    {
        return new MvcError("NOT_SET_TOOL: {$toolname}", self::NOT_SET_TOOL);
    }

    /**
     * 没有找到动作定义
     *
     * @param string $action_name
     *
     * @return MvcError
     */
    static function action_not_found_error($action_name)
    {
        return new MvcError("ACTION_NOT_FOUND: {$action_name}", self::ACTION_NOT_FOUND);
    }
}

