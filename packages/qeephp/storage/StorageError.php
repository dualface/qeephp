<?php

namespace qeephp\storage;

use qeephp\BaseError;

class StorageError extends BaseError
{
    const NOT_SET_DOMAIN_CONFIG     = 1;
    const CONNECT_FAILED            = 2;
    const QUERY_FAILED              = 3;
    const NOT_CONNECT               = 4;
    const NOT_SET_ID                = 100;
    const ENTITY_NOT_FOUND          = 101;
    const INVALID_CONFIG            = 102;
    const INCOMPLETE_MODEL_PROP     = 103;
    const UNKNOWN_MODEL_PROP        = 104;

    static function not_set_id_error($class_name)
    {
        return new StorageError("NOT_SET_ID: {$class_name}", self::NOT_SET_ID);
    }

    static function entity_not_found_error($class_name, $primary_key_value)
    {
        return new StorageError("ENTITY_NOT_FOUND: {$class_name}, {$primary_key_value}", self::ENTITY_NOT_FOUND);
    }

    static function invalid_config_error($class_name, $item_name)
    {
        return new StorageError("INVALID_CONFIG: {$class_name}, {$item_name}", self::INVALID_CONFIG);
    }

    static function incomplete_model_prop_error($class_name, $prop)
    {
        return new StorageError("INCOMPLETE_MODEL_PROP: {$class_name}, {$prop}", self::INCOMPLETE_MODEL_PROP);
    }

    static function unknown_model_prop_error($class_name, $prop)
    {
        return new StorageError("UNKNOWN_MODEL_PROP: {$class_name}, {$prop}", self::UNKNOWN_MODEL_PROP);
    }

    static function not_set_domain_config_error($domain)
    {
        return new StorageError("NOT_SET_DOMAIN_CONFIG: {$domain}", self::NOT_SET_DOMAIN_CONFIG);
    }

    static function connect_failed_error($raw_error_code, $raw_error_message, $domain)
    {
        return new StorageError("CONNECT_FAILED: {$domain}, {$raw_error_code}, {$raw_error_message}", self::CONNECT_FAILED);
    }

    static function query_failed_error($raw_error_code, $raw_error_message, $domain)
    {
        return new StorageError("QUERY_FAILED: {$domain}, {$raw_error_code}, {$raw_error_message}", self::QUERY_FAILED);
    }

    static function not_connect_error($domain)
    {
        return new StorageError("NOT_CONNECT: {$domain}", self::NOT_CONNECT);
    }
}

