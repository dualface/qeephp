<?php

namespace qeephp;

/**
 * 提供类和接口的自动载入服务
 */
abstract class Autoload
{
    /**
     * 类搜索路径
     *
     * @var array
     */
    private static $_paths = array();

    /**
     * 添加一个类搜索路径
     *
     * @param string $dir
     * @param string $namespace_prefix
     */
    static function import($dir, $namespace_prefix = null)
    {
        $dir = rtrim(realpath($dir), '/\\') . DS;
        self::$_paths[$dir] = ltrim($namespace_prefix, '\\');
    }

    /**
     * 载入指定类的定义文件，如果载入失败抛出异常
     *
     * @param string $class_name
     */
    static function _autoload($class_name)
    {
        if ($class_name{0} == '\\') $class_name = ltrim($class_name, '\\');
        if (strpos($class_name, '\\') === false)
        {
            $filename = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
        }
        else
        {
            $filename = str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
        }
        foreach (self::$_paths as $dir => $namespace_prefix)
        {
            if ($namespace_prefix)
            {
                $prefix_len = strlen($namespace_prefix);
                if (strncasecmp($class_name, $namespace_prefix, $prefix_len) !== 0) continue;
                $tmp_class_name = substr($class_name, $prefix_len);
                $tmp_filename = str_replace('\\', DIRECTORY_SEPARATOR, $tmp_class_name) . '.php';
                $tmp_filename = ltrim($tmp_filename, '\\');
                $path = $dir . $tmp_filename;
            }
            else
            {
                $path = $dir . $filename;
            }

            if (is_file($path))
            {
                require($path);
                return;
            }
        }
    }
}
