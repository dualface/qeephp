<?php

namespace qeephp\cache;

/**
 * Apc 提供进程内缓存服务
 */
class Apc implements ICache
{
    function get($key)
    {
        $success = false;
        $value = apc_fetch($key, $success);
        if ($success) return $value;
        return false;
    }

    function get_multi(array $keys)
    {
        $values = array();
        foreach ($keys as $key)
        {
            $values[$key] = $this->fetch($key);
        }
        return $values;
    }

    function set($key, $value, $ttl = null)
    {
        apc_store($key, $value, $ttl);
    }

    function set_multi(array $values, $ttl = null)
    {
        foreach ($values as $key => $value)
        {
            apc_store($key, $value, $ttl);
        }
    }

    function del($key)
    {
        apc_delete($key);
    }

    function del_multi(array $keys)
    {
        foreach ($keys as $key)
        {
            apc_delete($key);
        }
    }

    static function instance($domain)
    {
        static $instance;
        if (!$instance)
        {
            $instance = new Apc();
        }
        return $instance;
    }
}

