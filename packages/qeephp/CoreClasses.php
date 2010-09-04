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
        return array_key_exists($item, self::$_config) ? self::$_config[$item] : $default;
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

/**
 * 事件
 */
class Event
{
    /**
     * 事件名称
     *
     * @var string
     */
    public $name;

    /**
     * 事件响应方法
     *
     * @var array
     */
    public $listeners;

    /**
     * 指示事件是否已经处理
     *
     * @var bool
     */
    public $completed = false;

    /**
     * 是否继续传递事件
     *
     * @var bool
     */
    public $continue = true;

    /**
     * 事件的执行结果
     *
     * @var mixed
     */
    public $result;

    /**
     * 构造函数
     *
     * @param string $name
     * @param array $listeners
     */
    function __construct($name, array $listeners = null)
    {
        $this->name = $name;
        $this->listeners = $listeners;
    }

    /**
     * 追加事件处理方法
     *
     * @param array $listeners
     *
     * @return Event
     */
    function append_listeners(array $listeners)
    {
        $this->listeners += $listeners;
        return $this;
    }

    /**
     * 调度事件
     */
    function dispatching()
    {
        $this->dispatching_with_args(func_get_args());
    }

    /**
     * 使用多个参数调度事件
     *
     * @param array $args
     */
    function dispatching_with_args(array $args = null)
    {
        if (!is_array($args)) $args = array();
        array_unshift($args, $this);
        foreach ($this->listeners as $listener)
        {
            call_user_func_array($listener, $args);
            if (!$this->continue) break;
        }
    }
}

