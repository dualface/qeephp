<?php

namespace qeephp\cache;

use qeephp\BaseError;

/**
 * 与缓存有关的异常
 */
class CacheError extends BaseError
{
    const NOT_SET_DOMAIN_CONFIG = 1;

    /**
     * 没有提供指定存储域的设定
     *
     * @param string $domain
     *
     * @return CacheError
     */
    static function not_set_domain_config_error($domain)
    {
        return new static("NOT_SET_DOMAIN_CONFIG: {$domain}", self::NOT_SET_DOMAIN_CONFIG);
    }
}

