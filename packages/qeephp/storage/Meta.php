<?php

namespace qeephp\storage;

use qeephp\Config;
use qeephp\Event;
use qeephp\storage\inspector\Inspector;

/**
 * 管理对象与持久化设备间的映射
 *
 * 对模型的定义采用phpdoc标注。标注分为针对class和针对属性两类。
 *
 * ## 针对 class 的 phpdpc 标注 ##
 *
 * -  @domain
 *    存储域，如果未指定则使用默认存储域
 *
 * -  @collection
 *    存储对象实例的集合，如果不指定则使用小写的类名称作为集合名称
 *
 * -  @update
 *    将更新后的对象保存到存储集合时，应该采用的更新策略，
 *    默认设定为 all, check_all
 *
 * -  @extends by: 字段名 classes: 类名称=字段值
 *    定义单表继承，例如 @extends by: cls classes: Guest=0, Member=1, Admin=2
 *
 * -  @readonly
 *    定义是否是只读的对象实例，只读的对象从存储集合中读去出来后任何属性都不允许修改，
 *    并且不允许调用对象实例的 save() 等方法。
 *
 * -  @nonp
 *    对象实例不能保存到存储集合中
 *
 * -  @bind 插件名称 :设定=值
 * -  @bind config:配置名 :设定=值
 *    绑定插件，每一个 @bind 标注绑定一个插件。
 *    如果采用 @bind config: 的形式，则从指定的配置项读取插件设定。
 *
 *
 * ## 针对属性的 phpdoc 标注 ##
 *
 * -  @var 类型(长度)
 *    指定属性的类型，及存储长度（可选），例如 @var string(30)
 *
 * -  @id autoincr
 *    指定属性是否是主键，autoincr（可选）表示是否为自增主键
 *
 * -  @optional
 *    指示在保存对象实例到存储集合前，是否可以不设定该属性的值
 *
 * -  @internal
 *    指示该属性不受 Meta 管理
 *
 * -  @nonp
 *    指示属性不进行持久化保存
 *
 * -  @field 字段名
 *    指定属性存储到集合中时使用什么字段名
 *
 * -  @readonly
 *    定义只读属性。从存储集合中读取的对象实例，指定了 @readonly 的属性不允许修改
 *
 * -  @getter 方法名
 *    定义属性的 getter 方法
 *
 * -  @setter 方法名
 *    定义属性的 setter 方法
 *
 * -  @update
 *    将更新后的对象保存到存储集合时，针对特定属性应该采用的更新策略，
 *    默认设定为 overwrite
 */
class Meta implements IStorageDefine
{
    /* 类型的字符串名称到实际类型定义之间的映射 */
    static $type_map = array(
        'text'      => self::TYPE_TEXT,
        'string'    => self::TYPE_STRING,
        'int'       => self::TYPE_INT,
        'integer'   => self::TYPE_INT,
        'smallint'  => self::TYPE_SMALLINT,
        'bool'      => self::TYPE_BOOL,
        'boolean'   => self::TYPE_BOOL,
        'float'     => self::TYPE_FLOAT,
        'number'    => self::TYPE_FLOAT,
        'serial'    => self::TYPE_SERIAL,
    );

    /**
     * 模型的类名称
     *
     * @var string
     */
    public $class;

    /**
     * 主键名
     *
     * @var string|array
     */
    public $idname;

    /**
     * 自增主键名
     *
     * @var string
     */
    public $autoincr_idname;

    /**
     * 是否使用复合主键
     *
     * @var bool
     */
    public $composite_id = false;

    /**
     * 更新模式
     *
     * @var int
     */
    public $update = Meta::UPDATE_DEFAULT_POLICY;

    /**
     * 指示模型对象实例是否是只读
     *
     * @var bool
     */
    public $readonly = false;

    /**
     * 指示模型对象实例是否不允许保存到存储集合
     *
     * @var bool
     */
    public $nonp = false;

    /**
     * 模型的继承
     *
     * @var array
     */
    public $extends = null;

    /**
     * 是否使用继承
     *
     * @var bool
     */
    public $use_extends = false;

    /**
     * 模型使用的插件
     *
     * array(
     *   array(
     *     'class' => 插件类名称,
     *     ...
     *   ),
     *   ...
     * )
     *
     * @var array
     */
    public $bind = array();

    /**
     * 所有属性的设定
     *
     * array(
     *   name (string)
     *   type (self::TYPE_*)
     *   len (int)
     *   default (mixed)
     *   id (bool)
     *   autoincr (bool)
     *   optional (bool)
     *   nonp (bool)
     *   field (string)
     *   readonly (bool)
     *   getter (method_name | callback)
     *   setter (method_name | callback)
     *   update (Meta::UPDATE_PROP_*)
     * )
     *
     * @var array
     */
    public $props = array();

    /**
     * 指定了特殊更新模式的属性
     *
     * array(
     *   prop_name => Meta::UPDATE_PROP_*,
     *   ...
     * )
     *
     * @var array
     */
    public $spec_update_props = array();

    /**
     * 属性名和字段名的映射
     *
     * array(
     *   prop_name => field_name,
     *   ...
     * )
     *
     * @var array
     */
    public $props_to_fields = array();

    /**
     * 字段名和属性名的映射
     *
     * array(
     *   field_name => prop_name,
     *   ...
     * )
     *
     * @var array
     */
    public $fields_to_props = array();

    /**
     * 模型的静态方法
     *
     * array(
     *   method_name => callback,
     *   ...
     * )
     *
     * @var array
     */
    public $static_methods = array();

    /**
     * 模型的动态方法
     *
     * @var array
     */
    public $dynamic_methods = array();

    /**
     * 事件处理方法
     *
     * array(
     *   event_name => callback,
     *   ...
     * )
     *
     * @var array
     */
    public $events_listener = array();

    /**
     * 存储域的名称
     *
     * @var string
     */
    private $_domain;

    /**
     * 存储集合的名称
     *
     * @var string
     */
    private $_collection;

    /**
     * 已经实例化的 Meta 对象
     *
     * @var array
     */
    private static $_meta_instances = array();

    /**
     * 构造函数
     *
     * @param string $class
     */
    function __construct($class)
    {
        $this->class = $class;
        $this->_init();
    }

    /**
     * 取得指定类的 Meta 对象
     *
     * @param string $class
     *
     * @return Meta
     */
    static function instance($class)
    {
        if (!isset(self::$_meta_instances[$class]))
        {
            $cache = Config::get('storage.meta_cache', false) && function_exists('apc_fetch');
            $cache_key = "meta.instances.{$class}";
            $meta = null;
            /* @var $meta Meta */
            if ($cache)
            {
                $meta = apc_fetch($cache_key);
                if ($meta) $meta->bind_plugins($meta->bind);
            }
            if (!$meta) $meta = new Meta($class);
            if ($cache)
            {
                apc_store($cache_key, $meta, Config::get('storage.meta_cache_ttl', 60));
            }
            self::$_meta_instances[$class] = $meta;
        }
        return self::$_meta_instances[$class];
    }

    /**
     * 返回存储域名称
     *
     * @return string
     */
    function domain()
    {
        return !empty($this->_domain)
               ? $this->_domain
               : Config::get('storage.default_domain', 'default');
    }

    /**
     * 返回存储集合的名称
     *
     * @return string
     */
    function collection()
    {
        return $this->_collection;
    }

    /**
     * 添加事件处理函数
     *
     * @param string $event_name
     * @param callback $listener
     */
    function add_event_listener($event_name, $listener)
    {
        if (!is_callable($listener))
        {
            throw BaseError::not_callable_error();
        }
        if (!isset($this->events_listener[$event_name]))
        {
            $this->events_listener[$event_name] = array();
        }
        $this->events_listener[$event_name][] = $listener;
    }

    /**
     * 移除事件处理函数
     *
     * @param string $event_name
     * @param callback $listener
     */
    function remove_event_listener($event_name, $listener)
    {
        if (empty($this->events_listener[$event_name])) return;
        $offset = array_search($listener, $this->events_listener[$event_name], true);
        if ($offset !== false)
        {
            unset($this->events_listener[$event_name][$offset]);
        }
    }

    /**
     * 触发指定的事件，如果该事件有处理函数，则返回一个 Event 对象，否则返回 false
     *
     * @param string $event_name
     * @param array $args
     * @param BaseModel $model
     *
     * @return Event|bool
     */
    function raise_event($event_name, array $args = null, BaseModel $model = null)
    {
        if (empty($this->events_listener[$event_name])) return false;

        $event = new Event($event_name, $this->events_listener[$event_name]);
        if ($model)
        {
            if (!is_array($args)) $args = array();
            array_unshift($args, $model);
            // 如果提供了 $model 参数，则检查 $model 对象是否提供了事件处理方法
            if (method_exists($model, $event_name))
            {
                $event->append_listener(array($model, $event_name));
            }
        }
        $event->dispatching_with_args($args);
        return $event;
    }

    /**
     * 为模型添加一个静态方法
     *
     * @param string $method_name
     * @param callback $callback
     */
    function add_static_method($method_name, $callback)
    {
        if (!is_callable($callback))
        {
            throw BaseError::not_callable_error();
        }
        $this->static_methods[$method_name] = $callback;
    }

    /**
     * 为模型添加一个动态方法
     *
     * @param string $method_name
     * @param callback $callback
     */
    function add_dynamic_method($method_name, $callback)
    {
        if (!is_callable($callback))
        {
            throw BaseError::not_callable_error();
        }
        $this->dynamic_methods[$method_name] = $callback;
    }

    /**
     * 绑定插件
     *
     * @param array $plugins
     */
    function bind_plugins($plugins)
    {
        if (empty($plugins)) return;
        foreach ($plugins as $config)
        {
            if (!empty($config['config']))
            {
                $config_name = $config['config'];
                unset($config['config']);
                $config = array_merge((array)Config::get($config_name), $config);
            }

            $plugin_class = $config['class'];
            $plugin = new $plugin_class($config);
            $plugin->bind($this);
        }
    }

    /**
     * 将属性值数组转换为以字段名为键名的数组，并过滤掉不需要存储的值
     *
     * @param array $props
     *
     * @return array
     */
    function props_to_fields(array $props)
    {
        $record = array();
        foreach ($props as $name => $value)
        {
            if (!isset($this->props_to_fields[$name])) continue;
            $record[$this->props_to_fields[$name]] = $value;
        }
        return $record;
    }

    /**
     * 将字段数组转换为属性数组，并过滤掉不存在的属性
     *
     * @param array $record
     * 
     * @return array
     */
    function fields_to_props(array $record)
    {
        $return = array();
        foreach ($record as $field => $value)
        {
            if (!isset($this->fields_to_props[$field])) continue;
            $prop = $this->fields_to_props[$field];
            switch ($this->props[$prop]['type'])
            {
            case self::TYPE_INT:
            case self::TYPE_SMALLINT:
            case self::TYPE_SERIAL:
                $value = intval($value);
                break;

            case self::TYPE_FLOAT:
                $value = floatval($value);
                break;

            case self::TYPE_BOOL:
                $value = ($value) ? true : false;
                break;

            default:
                $value = (string)$value;
            }
            $return[$prop] = $value;
        }
        return $return;
    }

    /**
     * 从属性数组构造一个模型对象实例
     *
     * @param array $props
     *
     * @return class
     */
    function props_to_model(array $props)
    {
        if ($this->use_extends)
        {
            $by = $this->extends['by'];
            $type = $props[$by];
            $class = $this->extends['classes'][$type];
        }
        else
        {
            $class = $this->class;
        }
        $model = new $class();
        /* @var $model BaseModel */
        $model->__read($props);
        $this->raise_event(self::AFTER_READ_EVENT, null, $model);
        return $model;
    }

    /**
     * 初始化 Meta 对象
     */
    private function _init()
    {
        static $internals = array('domain' => '_domain', 'collection' => '_collection');
        $class = $this->class;
        $data = Inspector::inspect($class);
        foreach ($data as $key => $value)
        {
            if (isset($internals[$key])) $key = $internals[$key];
            $this->$key = $value;
        }
        $this->use_extends = (!empty($this->extends));
        $this->bind_plugins($this->bind);
        if (empty($this->idname))
        {
            throw ModelError::not_set_primary_key_error($this->class);
        }
    }

}

