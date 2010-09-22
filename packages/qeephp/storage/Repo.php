<?php

namespace qeephp\storage;

use qeephp\Config;
use qeephp\storage\adapter\IAdapterFinder;

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
            $record = $adapter->find_one($meta->collection(), $cond, null, $meta->props_to_fields);
        }
        if (!is_array($record))
        {
            if (is_array($cond)) $cond = http_build_query ($cond);
            throw StorageError::entity_not_found_error($class, $cond);
        }

        /**
         * 查找到数据后，会以主键值判断该数据是否已经在对象缓存中。如果缓存中找到了数据，则不构造新的模型对象，
         * 而是直接返回缓存的对象。也就是说这会导致读取的数据被抛弃。
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

        $model = self::props_to_model($meta, $props);
        self::$_objects[$cache_key] = $model;
        $meta->raise_event(self::AFTER_FINDONE_EVENT, array($cond, $model, $record));
        return $model;
    }

    /**
     * 按照主键值查询多个模型实例
     *
     * 仅能用于单主键的对象，$cond 参数为包含多个主键值的数组。
     *
     * @param string $class
     * @param array $cond
     *
     * @return array
     */
    static function find_multi($class, array $cond)
    {
        $meta = Meta::instance($class);
        if ($meta->composite_id)
        {
            throw StorageError::composite_id_not_implemented_error(__METHOD__);
        }

        $models = array();
        foreach ($cond as $offset => $id)
        {
            $cache_key = self::cache_key($class, $id);
            if (isset(self::$_objects[$cache_key]))
            {
                $models[$id] = self::$_objects[$cache_key];
                unset($cond[$offset]);
                continue;
            }
        }
        if (empty($cond)) return $models;

        $event = $meta->raise_event(self::BEFORE_FINDMULTI_EVENT, array($cond));
        if ($event && $event->completed && is_array($event->result))
        {
            $records = $event->result;
            $not_founds = array_diff($cond, array_keys($records));
        }
        else
        {
            $records = array();
            $not_founds = $cond;
        }

        if (!empty($not_founds))
        {
            $idfield = $meta->props_to_fields[$meta->idname];
            $adapter = self::select_adapter($meta->domain());
            $finder  = $adapter->find($meta->collection(), array($idfield => array($not_founds)));
            $finder->each(function ($record) use (& $records, $idfield) {
                $records[$record[$idfield]] = $record;
            });
        }

        $more_models = array();
        foreach ($records as $record)
        {
            $props = $meta->fields_to_props($record);
            $id = $props[$meta->idname];
            $cache_key = self::cache_key($class, $id);
            if (isset(self::$_objects[$cache_key]))
            {
                $models[$id] = self::$_objects[$cache_key];
                continue;
            }

            $model = self::props_to_model($meta, $props);
            self::$_objects[$cache_key] = $model;
            $models[$id] = $model;
            $more_models[$id] = $model;
        }
        $meta->raise_event(self::AFTER_FINDMULTI_EVENT, array($cond, $more_models, $records));
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
        $finder = $adapter->find($meta->collection(), $cond, null, $meta->props_to_fields);
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
        $meta = $model->get_meta();
        $event = $meta->raise_event(self::BEFORE_SAVE_EVENT, null, $model);
        $is_create = $model->is_fresh();
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
        if (!$meta) $meta = $model->get_meta();
        $meta->raise_event(self::BEFORE_CREATE_EVENT, null, $model);
        $record = $meta->props_to_fields($model->__to_array());
        $adapter = self::select_adapter($meta->domain(), $model);
        $id = $adapter->insert($meta->collection(), $record);
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
        if (!$meta) $meta = $model->get_meta();
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
        if ($model->is_fresh())
        {
            throw StorageError::del_new_instance_error($model->get_meta()->class);
        }

        $meta = $model->get_meta();
        $meta->raise_event(self::BEFORE_DEL_EVENT, array($model), $model);
        $cond = ($meta->composite_id) ? $model->id() : array($meta->idname => $model->id());
        $adapter = self::select_adapter($meta->domain(), $model);
        $result = $adapter->del($meta->collection(), $cond, $meta->props_to_fields);
        $meta->raise_event(self::AFTER_DEL_EVENT, array($model, $result), $model);
        $cache_key = self::cache_key($meta->class, $model->id());
        unset(self::$_objects[$cache_key]);
        if (is_int($result) && $result > 1)
        {
            throw StorageError::unexpected_del_error($meta->class, $result);
        }
        return $result == 1;
    }

    /**
     * 删除符合条件的对象，返回被删除对象的总数
     *
     * @param string $class
     * @param mixed $cond
     *
     * @return int
     */
    static function del_by($class, $cond)
    {
        $finder = $class::find($cond);
        /* @var $finder IAdapterFinder */
        $count = 0;
        $finder->each(function ($obj) use (& $count) {
            if ($obj->del()) $count++;
        });
        return $count;
    }

    /**
     * 从存储中直接删除一个对象
     *
     * @param string $class
     * @param mixed $id
     *
     * @return bool
     */
    static function erase_one($class, $id)
    {
        $meta = Meta::instance($class);
        if (!is_array($id))
        {
            if ($meta->composite_id)
            {
                throw StorageError::composite_id_not_implemented_error(__METHOD__);
            }
            $id = array($meta->idname => $id);
        }
        $result = self::erase_by($class, $id);
        if (is_int($result) && $result > 1)
        {
            throw StorageError::unexpected_del_error($class, $result);
        }
        return $result == 1;
    }

    /**
     * 直接从存储中删除符合条件的对象，返回被删除对象的总数
     *
     * @param string $class
     * @param mixed $cond
     *
     * @return int
     */
    static function erase_by($class, $cond)
    {
        $meta = Meta::instance($class);
        if (is_int($cond))
        {
            if ($meta->composite_id)
            {
                throw StorageError::composite_id_not_implemented_error(__METHOD__);
            }
            $cond = array($meta->idname => $cond);
        }
        $meta->raise_event(self::BEFORE_ERASE_EVENT, array($cond));
        $adapter = self::select_adapter($meta->domain(), $cond);
        $result = $adapter->del($meta->collection(), $cond, $meta->props_to_fields);
        $meta->raise_event(self::AFTER_ERASE_EVENT, array($cond, $result));
        return $result;
    }

    static function clean_cache($class = null, $id = null)
    {
        if (!is_null($id))
        {
            unset(self::$_objects[self::cache_key($class, $id)]);
        }
        else
        {
            self::$_objects = array();
        }
    }

    static function props_to_model(Meta $meta, array $props)
    {
        if ($meta->use_extends)
        {
            $by = $meta->extends['by'];
            $type = $props[$by];
            $class = $meta->extends['classes'][$type];
        }
        else
        {
            $class = $meta->class;
        }
        $model = new $class();
        /* @var $model BaseModel */
        $model->__read($props);
        return $model;
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

