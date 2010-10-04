<?php

namespace qeephp;

/**
 * 用与保存和读取应用程序设置的工具类
 */
abstract class Config
{
    /**
     * 应用程序设置
     *
     * @var array
     */
    private static $_config = array();

    /**
     * 导入设置
     *
     * @param array $config
     */
    static function import(array $config)
    {
        self::$_config = array_merge(self::$_config, $config);
    }

    /**
     * 读取指定的设置，如果不存在则返回$default参数指定的默认值
     *
     * @param string $item
     * @param mixed $default
     *
     * @return mixed
     */
    static function get($item, $default = null)
    {
        return isset(self::$_config[$item]) ? self::$_config[$item] : $default;
    }

    /**
     * 修改指定的设置
     *
     * @param string $item
     * @param mixed $value
     */
    static function set($item, $value)
    {
        self::$_config[$item] = $value;
    }
}

