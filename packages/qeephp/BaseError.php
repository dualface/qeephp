<?php

namespace qeephp;

/**
 * QeePHP 所有异常的基础类
 */
class BaseError extends \Exception
{
    const NOT_IMPLEMENTED = 0x9991;
    const TYPE_MISMATCH   = 0x9992;
    const NOT_CALLABLE    = 0x9993;

    /**
     * 构造函数
     *
     * @param string $message
     * @param int $code
     */
    function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * 尝试调用未实现的方法
     *
     * @param string $method_name
     *
     * @return BaseError
     */
    static function not_implemented_error($method_name)
    {
        return new BaseError("NOT_IMPLEMENTED: {$method_name}", self::NOT_IMPLEMENTED);
    }

    /**
     * 类型不匹配
     *
     * @param string $expected_type
     * @param string $actual_type
     * 
     * @return BaseError
     */
    static function type_mismatch_error($expected_type, $actual_type)
    {
        return new BaseError("TYPE_MISMATCH: {$expected_type}, {$actual_type}", self::TYPE_MISMATCH);
    }

    /**
     * 无法调用的对象
     *
     * @return BaseError
     */
    static function not_callable_error()
    {
        return new BaseError("NOT_CALLABLE", self::NOT_CALLABLE);
    }
}

