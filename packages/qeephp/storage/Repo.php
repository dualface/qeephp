<?php

namespace qeephp\storage;

use qeephp\Config;
use qeephp\storage\adapter\IAdapterFinder;

abstract class Repo implements IStorageDefine
{
    private static $_adapter_instances = array();

    /**
     * 为特定存储域选择匹配的存储服务实例
     *
     * @param string $domain
     *
     * @return qeephp\storage\adapter\IAdapter
     */
    static function select_adapter($domain)
    {
        if (!isset(self::$_adapter_instances[$domain]))
        {
            $config = Config::get("storage.domains.{$domain}");
            if (empty($config)) throw StorageError::not_set_domain_config_error($domain);
            $class = $config['class'];
            self::$_adapter_instances[$domain] = new $class($config);
        }
        return self::$_adapter_instances[$domain];
    }

    /**
     * 按照主键值查询指定的对象
     *
     * @param string $class
     * @param mixed $id
     *
     * @return BaseModel
     */
    static function find_one($class, $id)
    {
        $meta = Meta::instance($class);
        $cond = self::get_cond_from_id($meta, $id);
        $event = $meta->raise_event(self::BEFORE_FIND_EVENT, array($cond, 'find_one'));
        if ($event && $event->completed && ($event->result instanceof BaseModel))
        {
            $model = $event->result;
        }
        else
        {
            $adapter = self::select_adapter($meta->domain());
            $record = $adapter->find_one($meta->collection(), $cond, null, $meta->props_to_fields);
            if (!is_array($record)) throw StorageError::entity_not_found_error($class, $cache_key);
            $model = $meta->props_to_model($meta->fields_to_props($record));
        }

        if (self::cache_key($class, $model->id(true)) != self::cache_key($class, $cond))
        {
            throw StorageError::unknown_error('unexpected find_one() result, mismatch id.');
        }
        $meta->raise_event(self::AFTER_FIND_EVENT, array($cond, 'find_one', array($model)));
        return $model;
    }

    /**
     * 按照主键值查询多个对象
     *
     * @param string $class
     * @param array $id_list
     *
     * @return array
     */
    static function find_multi($class, array $id_list)
    {
        $meta = Meta::instance($class);
        if ($meta->composite_id) throw StorageError::composite_id_not_implemented_error(__METHOD__);

        $event = $meta->raise_event(self::BEFORE_FIND_EVENT, array($id_list, 'find_multi'));
        if ($event && $event->completed && is_array($event->result))
        {
            $models = $event->result;
            $query_id_list = array_diff($id_list, array_keys($models));
        }
        else
        {
            $models = array();
            $query_id_list = $id_list;
        }

        if (!empty($query_id_list))
        {
            $idname  = $meta->idname;
            $idfield = $meta->props_to_fields[$idname];
            $adapter = self::select_adapter($meta->domain());
            $finder  = $adapter->find($meta->collection(), array($idfield => $query_id_list))
                               ->set_model_class($class);
            while ($model = $finder->fetch())
            {
                $models[$model->$idname] = $model;
            }
        }

        $meta->raise_event(self::AFTER_FIND_EVENT, array($id_list, 'find_multi', $models));
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
        $adapter = self::select_adapter($meta->domain());
        return $adapter->find($meta->collection(), $cond, null, $meta->props_to_fields)
                       ->set_model_class($class);
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
        $adapter = self::select_adapter($meta->domain());
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
        $adapter = self::select_adapter($meta->domain());
        $result = $adapter->update_model($model, $meta);
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
        $meta->raise_event(self::BEFORE_DEL_EVENT, null, $model);
        $cond = ($meta->composite_id) ? $model->id() : array($meta->idname => $model->id());
        $adapter = self::select_adapter($meta->domain());
        $result = $adapter->del($meta->collection(), $cond, $meta->props_to_fields);
        $meta->raise_event(self::AFTER_DEL_EVENT, array($result), $model);
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
        $adapter = self::select_adapter($meta->domain());
        $result = $adapter->del($meta->collection(), $cond, $meta->props_to_fields);
        $meta->raise_event(self::AFTER_ERASE_EVENT, array($cond, $result));
        return $result;
    }

    static function cache_key($class, array $id)
    {
        if (count($id) > 1) ksort($id, SORT_ASC);
        $key = http_build_query($id);
        return "{$class}.{$key}";
    }

    static function get_cond_from_id(Meta $meta, $id)
    {
        static $error_composite_id = 'invalid parameter $id, with composite_id.';
        static $error_one_id = 'invalid parameter $id';

        $cond = array();
        if ($meta->composite_id)
        {
            if (!is_array($id))
            {
                throw StorageError::invalid_parameters_error($error_composite_id);
            }
            foreach ($meta->idname as $idname)
            {
                if (!isset($id[$idname]) || strlen($id[$idname]) == 0)
                {
                    throw StorageError::invalid_parameters_error($error_composite_id);
                }
                $cond[$idname] = $id[$idname];
                unset($id[$idname]);
            }
            if (!empty($id))
            {
                throw StorageError::invalid_parameters_error($error_composite_id);
            }
        }
        else
        {
            if (is_array($id))
            {
                reset($id);
                if (count($id) > 1 || key($id) != $meta->idname)
                {
                    throw StorageError::invalid_parameters_error($error_one_id);
                }
                $cond = $id;
            }
            else
            {
                $cond = array($meta->idname => $id);
            }
        }
        return $cond;
    }
}

