<?php

namespace qeephp\interfaces;

/**
 * 缓存服务接口
 */
interface ICache
{
    /**
     * 尝试从缓存中读取指定的数据，如果数据不存在或读取失败返回 false
     *
     * @param mixed $key
     *
     * @return mixed
     */
    function get($key);

    /**
     * 尝试从缓存中读取多个数据，如果读取失败返回 false
     *
     * 返回的数组中仅包含读取成功的数据。
     *
     * @param array $keys
     *
     * @return array
     */
    function mget(array $keys);

    /**
     * 将数据放入缓存
     *
     * @param mixed $key
     * @param mixed $value
     * @param int $ttl
     */
    function set($key, $value, $ttl = null);

    /**
     * 缓存多个数据项
     *
     * @param array $values
     * @param int $ttl
     */
    function mset(array $values, $ttl = null);

    /**
     * 删除数据
     *
     * @param mixed $key
     */
    function del($key);

    /**
     * 删除多个项
     *
     * @param array $keys
     */
    function mdel(array $keys);

    /**
     * 返回用于特定存储域的缓存服务对象实例
     *
     * @param string $domain
     *
     * @return ICache
     */
    static function instance($domain);
}
