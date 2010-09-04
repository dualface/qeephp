<?php

namespace qeephp\interfaces;

/**
 * 日志服务接口
 */
interface ILogService
{
    /**
     * 追加一条日志条目
     *
     * @param string $message
     * @param bool $ignore_debug_mode 是否忽略 QEE_DBEUG 设定
     */
    function append($message, $ignore_debug_mode = false);
}

