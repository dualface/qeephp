<?php

namespace qeephp\storage\plugins;

use qeephp\cache\ICache;
use qeephp\storage\Meta;
use qeephp\storage\ModelEvent;
use qeephp\errors\StorageError;

class CachePlugin extends BasePlugin
{
    /**
     * 缓存服务对象
     *
     * @var ICache
     */
    private $_cache;

    /**
     * 缓存有效期
     *
     * @var int
     */
    private $_cacheTtl;

    /**
     * 缓存键名前缀
     *
     * @var string
     */
    private $_cacheKeyPrefix;

    /**
     * 已经缓存的主键值
     *
     * @var array
     */
    private $_cachedPrimaryKeyValues = array();

    /**
     * 针对不同存储域的缓存对象
     *
     * @var array
     */
    private static $_backendOfDomains = array();

    /**
     * 构造函数
     *
     * @param Meta $meta
     * @param array $config
     */
    protected function __construct(Meta $meta, array $config)
    {
        parent::__construct($meta, $config);
        $this->_cacheMode = strtolower(val($config, 'cacheMode', 'afterRead'));
        $this->_cacheTtl = val($config, 'cacheTtl', 0);
        $this->_cacheKeyPrefix = "models\\entity\\{$meta->className}\\";
    }

    function bind()
    {
        $listeners = array();
        if (strpos($this->_cacheMode, 'bypassonread') === false)
        {
            $listeners[Meta::BEFORE_FIND_ONE_EVENT] = array($this, '__beforeFindOne');
            $listeners[Meta::BEFORE_FIND_MULTI_EVENT] = array($this, '__beforeFindMulti');
        }
        if (strpos($this->_cacheMode, 'afterread') !== false)
        {
            $listeners[Meta::AFTER_FIND_ONE_EVENT] = array($this, '__afterFindOne');
            $listeners[Meta::AFTER_FIND_MULTI_EVENT] = array($this, '__afterFindMulti');
        }
        if (strpos($this->_cacheMode, 'aftersave') !== false)
        {
            $listeners[Meta::AFTER_SAVE_EVENT] = array($this, '__afterSave');
        }
        $listeners[Meta::AFTER_DEL_EVENT] = array($this, '__afterDel');
        $listeners[Meta::AFTER_ERASE_EVENT] = array($this, '__afterDel');
        return $listeners;
    }

    function __beforeFindOne(ModelEvent $event, $primaryKeyValue)
    {
        $cacheId = $this->_cacheKeyPrefix . $primaryKeyValue;
        $record = $this->_cache()->fetch($cacheId);

        if (is_array($record))
        {
            $event->completed = true;
            $event->result = $record;
            $this->_cachedPrimaryKeyValues[$primaryKeyValue] = true;
        }
    }

    function __beforeFindMulti(ModelEvent $event, array $primaryKeyValues)
    {
        $cacheIds = array();
        foreach ($primaryKeyValues as $primaryKeyValue)
        {
            $cacheIds[] = $this->_cacheKeyPrefix . $primaryKeyValue;
        }
        $records = $this->_cache()->fetchMulti($cacheIds);

        if (is_array($records))
        {
            $event->completed = true;
            $event->result = $records;
            foreach ($records as $primaryKeyValue => $record)
            {
                $this->_cachedPrimaryKeyValues[$primaryKeyValue] = true;
            }
        }
    }

    function __afterFindOne(ModelEvent $event, $primaryKeyValue,  IModel $model,  array $record)
    {
        if (isset($this->_cachedPrimaryKeyValues[$primaryKeyValue])) return;

        $cacheId = $this->_cacheKeyPrefix . $primaryKeyValue;
        $this->_cache()->store($cacheId, $record, $this->_cacheTtl);
        $event->completed = true;
    }

    function __afterFindMulti(ModelEvent $event, array $primaryKeyValues, array $models, array $records)
    {
        foreach ($primaryKeyValues as $primaryKeyValue)
        {
            if (isset($this->_cachedPrimaryKeyValues[$primaryKeyValue])) return;

            $cacheId = $this->_cacheKeyPrefix . $primaryKeyValue;
            $this->_cache()->store($cacheId, $records[$primaryKeyValue], $this->_cacheTtl);
        }
        $event->completed = true;
    }

    function __afterSave(ModelEvent $event, IModel $model, $result)
    {
        $primaryKeyValue = $model->id();
        $cacheId = $this->_cacheKeyPrefix . $primaryKeyValue;
        $this->_cache()->store($cacheId, $model->__toArray(false), $this->_cacheTtl);
        $this->_cachedPrimaryKeyValues[$primaryKeyValue] = true;
        $event->completed = true;
    }

    function __afterDel(ModelEvent $event, IModel $model, $result)
    {
        if ($result)
        {
            $cacheId = $this->_cacheKeyPrefix . $model->id();
            $this->_cache()->del($cacheId);
        }
    }

    function __afterErase(ModelEvent $event, $primaryKeyValue, $result)
    {
        if ($result)
        {
            $this->_cache()->del($this->_cacheKeyPrefix . $primaryKeyValue);
        }
    }

    /**
     * 返回缓存后端
     *
     * @return ICache
     */
    private function _cache()
    {
        if (!$this->_cache)
        {
            $domain = val($this->_config, 'domain', 'default');
            if (!isset(self::$_backendOfDomains[$domain]))
            {
                $backendClassName = val($this->_config, 'backend');
                $backend = call_user_func(array($backendClassName, 'instance'), $domain);
                self::$_backendOfDomains[$domain] = $backend;
            }
            $this->_cache = self::$_backendOfDomains[$domain];
        }
        return $this->_cache;
    }

}

