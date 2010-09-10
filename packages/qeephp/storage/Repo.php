<?php

namespace qeephp\storage;

use qeephp\Config;

abstract class Repo implements IStorageDefine
{
    private static $_objects = array();
    private static $_domains_dispatcher = array();
    private static $_adapter_instances = array();

    /**
     * 设定特定存储域的调度器
     *
     * @param string $domain
     * @param callback $dispatcher
     */
    static function set_dispatcher($domain, $dispatcher)
    {
        if (!is_callable($dispatcher))
        {
            throw StorageError::not_callable_error();
        }
        self::$_domains_dispatcher[$domain] = $dispatcher;
    }

    /**
     * 删除特定存储域的调度器
     *
     * @param string $domain
     */
    static function del_dispatcher($domain)
    {
        unset(self::$_domains_dispatcher[$domain]);
    }

    /**
     * 为特定存储域选择匹配的存储服务实例
     *
     * @param string $domain
     *
     * @return qeephp\storage\adapter\IAdapter
     */
    static function select_adapter($domain)
    {
        $key = $domain;
        if (isset(self::$_domains_dispatcher[$domain]))
        {
            $dispatcher = self::$_domains_dispatcher[$domain];
            $node = call_user_func_array($dispatcher, func_get_args());
            if (strlen($node) > 0) $key .= ".{$node}";
        }

        if (!isset(self::$_adapter_instances[$key]))
        {
            $config = Config::get("storage.domains.{$key}");
            if (empty($config))
            {
                throw StorageError::not_set_domain_config_error($key);
            }
            $class = $config['class'];
            $adapter = new $class($config);
            self::$_adapter_instances[$key] = $adapter;
        }
        return self::$_adapter_instances[$key];
    }

    /**
     * 查找一个对象
     *
     * @param string $class
     * @param mixed $cond
     *
     * @return BaseModel
     */
    static function find_one($class, $cond)
    {
        $meta = Meta::instance($class);
        if (is_int($cond))
        {
            $cache_key = self::cache_key($class, $cond);
            if (isset(self::$_objects[$cache_key])) return self::$_objects[$cache_key];
            $cond = array($meta->idname => $cond);
        }

        $event = $meta->raise_event(self::BEFORE_FINDONE_EVENT, array($cond));
        if ($event && $event->completed && is_array($event->result))
        {
            $record = $event->result;
        }
        else
        {
            $adapter = self::select_adapter($meta->domain());
            /* @var $adapter IAdapter */
            $record = $adapter->find_one($meta->collection, $cond, null, $meta->props_to_fields);
        }
        if (!is_array($record))
        {
            if (is_array($cond)) $cond = http_build_query ($cond);
            throw StorageError::entity_not_found_error($class, $cond);
        }

        /**
         * 查找到数据后，会以主键值判断该数据是否已经在对象缓存中。如果缓存中找到了数据，则不构造新的模型对象，
         * 而是直接返回缓存的对象。也就是说这会导致读取的数据被抛弃。
         *
         * 因此为了获得最好的性能，应该总是使用主键值调用 find_one() 方法进行查询。
         */
        $props = $meta->fields_to_props($record);
        if ($meta->composite_id)
        {
            $id = array();
            foreach ($meta->idname as $idname)
            {
                $id[$idname] = $props[$idname];
            }
        }
        else
        {
            $id = $props[$meta->idname];
        }
        $cache_key = self::cache_key($class, $id);
        if (isset(self::$_objects[$cache_key])) return self::$_objects[$cache_key];

        if ($meta->use_extends)
        {
            $by = $meta->extends['by'];
            $type = $record[$by];
            $class = $meta->extends['classes'][$type];
        }

        $model = new $class();
        /* @var $model BaseModel */
        $model->__read($props);
        $meta->raise_event(self::AFTER_FINDONE_EVENT, array($cond, $model, $record));

        self::$_objects[$cache_key] = $model;
        return $model;
    }

    /**
     * 查找多个主键值的对象
     *
     * @param string $class
     * @param array $id_list
     *
     * @return array
     */
    static function find_multi($class, array $id_list)
    {
        $meta = Meta::instance($class);
        if ($meta->composite_id)
        {
            throw StorageError::not_implemented_error(__METHOD__ . ' with composite id');
        }

        $models = array();
        foreach ($id_list as $offset => $id)
        {
            $key = self::cache_key($class, $id);
            if (isset(self::$_objects[$key]))
            {
                $models[$id] = self::$_objects[$key];
                unset($id_list[$offset]);
            }
        }
        if (empty($id_list)) return $models;

        $event = $meta->raise_event(self::BEFORE_FINDMULTI_EVENT, array($id_list));
        if ($event && $event->completed && is_array($event->result))
        {
            $records = $event->result;
            $not_founds = array_diff($id_list, array_keys($records));
        }
        else
        {
            $records = array();
            $not_founds = $id_list;
        }

        if (!empty($not_founds))
        {
            $idfield = $meta->props_to_fields[$meta->idname];
            $adapter = self::select_adapter($meta->domain());
            $adapter->find($meta->collection, array($idfield => array($not_founds)))
                    ->each(function ($record) use (& $records, $idfield) {
                        $records[$record[$idfield]] = $record;
                    });
        }

        $more_models = self::records_to_models($meta, $records);
        $meta->raise_event(self::AFTER_FINDMULTI_EVENT, array($id_list, $more_models, $records));

        foreach ($more_models as $id => $model)
        {
            $key = self::cache_key($class, $id);
            self::$_objects[$key] = $model;
            $models[$id] = $model;
        }

        return $models;
    }

    /**
     * 构造一个查询对象
     *
     * @param string $class
     * @param mixed $cond
     *
     * @return IAdapterFinder
     */
    static function find($class, $cond)
    {
        $meta = Meta::instance($class);
        $adapter = self::select_adapter($meta->domain(), $cond);
        $finder = $adapter->find($meta->collection, $cond, null, $meta->props_to_fields);
        $finder->set_model_class($class);
        return $finder;
    }

    /**
     * 保存一个对象，如果是新建对象返回对象主键值，更新则返回更新情况
     *
     * @param BaseModel $model
     *
     * @return mixed
     */
    static function save(BaseModel $model)
    {
        $meta = $model->my_meta();
        $event = $meta->raise_event(self::BEFORE_SAVE_EVENT, null, $model);
        $is_create = $model->is_new();
        $result = ($is_create) ? self::create($model, $meta) : self::update($model, $meta);
        $meta->raise_event(self::AFTER_SAVE_EVENT, array($result), $model);
        return $result;
    }

    /**
     * 在存储中创建对象，并返回新建对象的主键值
     *
     * @param BaseModel $model
     * @param Meta $meta
     *
     * @return mixed
     */
    static function create(BaseModel $model, Meta $meta = null)
    {
        if (!$meta) $meta = $model->my_meta();
        $meta->raise_event(self::BEFORE_CREATE_EVENT, null, $model);
        $record = $meta->props_to_fields($model->__to_array());
        $adapter = self::select_adapter($meta->domain(), $model);
        $id = $adapter->insert($meta->collection, $record);
        $model->__save(true, $id);
        $meta->raise_event(self::AFTER_CREATE_EVENT, array($id), $model);
        return $model->id();
    }

    /**
     * 更新对象，如果存储服务确实进行了更新操作，则返回 true，否则返回 false
     *
     * @param BaseModel $model
     * @param Meta $meta
     *
     * @return bool
     */
    static function update(BaseModel $model, Meta $meta = null)
    {
        $changes = $model->changes();
        if (empty($changes)) return false;
        if (!$meta) $meta = $model->my_meta();
        $meta->raise_event(self::BEFORE_UPDATE_EVENT, null, $model);
        $result = self::select_adapter($meta->domain(), $model)->update_model($model, $meta);
        if ($result) $model->__save(false);
        $meta->raise_event(self::AFTER_UPDATE_EVENT, array($result), $model);
        return $result;
    }

    /**
     * 删除对象，删除成功返回 true
     *
     * @param BaseModel $model
     *
     * @return bool
     */
    static function del(BaseModel $model)
    {
        $event = $model->__beforeDel();
        $this->raise_event(self::BEFORE_DEL_EVENT, array($model), $event);

        $primaryKey = $this->idname;
        $primaryKeyValue = $model->$primaryKey;

        $result = $this->adapter()->del($this->collection,
                                         $this->props_to_fields[$primaryKey],
                                         $primaryKeyValue);

        $this->raise_event(self::AFTER_DEL_EVENT, array($model, $result));
        $model->__afterDel($result);
        unset(self::$_objects[$id]);
        return $result;
    }

    /**
     * 直接删除对象，不会构造对象实例，成功返回 true
     *
     * @param mixed $primaryKeyValue
     *
     * @return bool
     */
    static function erase($primaryKeyValue)
    {
        $event = call_user_func(array($this->class, '__beforeErase'), $primaryKeyValue);
        $this->raise_event(self::BEFORE_ERASE_EVENT, array($primaryKeyValue), $event);
        $result = $this->adapter()->del($this->collection,
                                         $this->props_to_fields[$this->idname],
                                         $primaryKeyValue);
        $this->raise_event(self::AFTER_ERASE_EVENT, array($primaryKeyValue, $result));
        call_user_func(array($this->class, '__afterErase'), $primaryKeyValue, $result);
        return $result;
    }

    static function clean_cache()
    {
        self::$_objects = array();
    }

        {
        }
    }

    static function records_to_models(Meta $meta, array $records)
    {
        $models = array();
        $idname = $meta->idname;

        if ($meta->use_extends)
        {
            $by = $meta->extends['by'];
        }
        $class = $meta->class;

        foreach ($records as $id => $record)
        {
            if ($meta->use_extends)
            {
                $type = $record[$by];
                $class = $meta->extends['classes'][$type];
            }
            $model = new $class();
            /* @var $model BaseModel */
            $model->__read(self::record_to_props($meta, $record));
            $models[$id] = $model;
        }

        return $models;
    }

    static function cache_key($class, $id)
    {
        if (is_array($id))
        {
            $key = http_build_query($id);
        }
        else
        {
            $key = (string)$id;
        }

        return "{$class}.{$key}";
    }
}

