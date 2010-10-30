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
    public static $_config = array();

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
        if (strpos($item, '/') === false)
        {
            return array_key_exists($item, self::$_config) ? self::$_config[$item] : $default;
        }

        list($keys, $last) = self::_get_nested_keys($item);
        $config =& self::$_config;
        foreach ($keys as $key)
        {
            if (array_key_exists($key, $config))
            {
                $config =& $config[$key];
            }
            else
            {
                return $default;
            }
        }
        return array_key_exists($last, $config) ? $config[$last] : $default;
    }

    /**
     * 修改指定的设置
     *
     * @param string $item
     * @param mixed $value
     */
    static function set($item, $value)
    {
        if (strpos($item, '/') === false)
        {
            self::$_config[$item] = $value;
        }

        list($keys, $last) = self::_get_nested_keys($item);
        $config =& self::$_config;
        foreach ($keys as $key)
        {
            if (!array_key_exists($key, $config))
            {
                $config[$key] = array();
            }
            $config =& $config[$key];
        }
        $config[$last] = $value;
    }

    static private function _get_nested_keys($key)
    {
        $keys = arr($key, '/');
        $last = array_pop($keys);
        return array($keys, $last);
    }
}

