<?php

namespace qeephp\cache;

use qeephp\Config;
use qeephp\errors\CacheError;

/**
 * Memcached 提供分布式缓存服务
 */
class Memcached implements ICache
{
    /**
     * 存储域名称
     *
     * @var string
     */
    private $_domain;

    /**
     * Memcached 对象
     *
     * @var \Memcached
     */
    private $_memcached;

    function __construct($domain)
    {
        $this->_domain = $domain;
        $memcached_config = Config::get(__CLASS__);
        $domains_config = val($memcached_config, 'domains');
        if (!is_array($domains_config) || empty($domains_config[$domain]))
        {
            throw CacheError::notSetDomainConfigError($domain);
        }
        $domain_config = $domains_config[$domain];
        if (!is_array($domain_config))
        {
            throw CacheError::notSetDomainConfigError($domain);
        }

        $this->_memcached = new \Memcached($domain);
        $first = reset($domain_config);
        $first_key = key($domain_config);
        if (!is_int($first_key)) $domain_config = array($domain_config);
        $this->_memcached->addServers($domain_config);
    }

    function get($key)
    {
        return $this->_memcached->get($key);
    }

    function mget(array $keys)
    {
        return $this->_memcached->getMulti($keys);
    }

    function set($key, $value, $ttl = null)
    {
        $this->_memcached->set($key, $value, $ttl);
    }

    function mset(array $values, $ttl = null)
    {
        $this->_memcached->setMulti($values, $ttl);
    }

    function del($key)
    {
        $this->_memcached->delete($key);
    }
    
    function mdel(array $keys)
    {
        foreach ($keys as $key)
        {
            $this->_memcached->delete($key);
        }
    }

    function __call($method, $arguments)
    {
        return call_user_func_array(array($this->_memcached, $method), $arguments);
    }

    static function instance($domain)
    {
        static $instances = array();
        if (!isset($instances[$domain]))
        {
            $instances[$domain] = new Memcached($domain);
        }
        return $instances[$domain];
    }
}

