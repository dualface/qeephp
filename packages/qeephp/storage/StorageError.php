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
    const ENTITY_NOT_SAVED          = 105;
    const DEL_NEW_INSTANCE          = 106;
    const COMPOSITE_ID_NOT_IMPLEMENTED = 107;
    const UNEXPECTED_DELETE         = 108;

    static function not_set_id_error($class_name)
    {
        return new static("NOT_SET_ID: {$class_name}", self::NOT_SET_ID);
    }

    static function entity_not_found_error($class_name, $primary_key_value)
    {
        return new static("ENTITY_NOT_FOUND: {$class_name}, {$primary_key_value}", self::ENTITY_NOT_FOUND);
    }

    static function invalid_config_error($class_name, $item_name)
    {
        return new static("INVALID_CONFIG: {$class_name}, {$item_name}", self::INVALID_CONFIG);
    }

    static function incomplete_model_prop_error($class_name, $prop)
    {
        return new static("INCOMPLETE_MODEL_PROP: {$class_name}, {$prop}", self::INCOMPLETE_MODEL_PROP);
    }

    static function unknown_model_prop_error($class_name, $prop)
    {
        return new static("UNKNOWN_MODEL_PROP: {$class_name}, {$prop}", self::UNKNOWN_MODEL_PROP);
    }

    static function entity_not_saved_error($class_name, $id)
    {
        if (is_array($id)) $id = http_build_query ($id);
        return new static("ENTITY_NOT_SAVED: {$class_name}, {$id}");
    }

    static function not_set_domain_config_error($domain)
    {
        return new static("NOT_SET_DOMAIN_CONFIG: {$domain}", self::NOT_SET_DOMAIN_CONFIG);
    }

    static function connect_failed_error($raw_error_code, $raw_error_msg, $domain)
    {
        return new static("CONNECT_FAILED: {$domain}, {$raw_error_code}, {$raw_error_msg}", self::CONNECT_FAILED);
    }

    static function query_failed_error($raw_error_code, $raw_error_msg, $domain)
    {
        return new static("QUERY_FAILED: {$domain}, {$raw_error_code}, {$raw_error_msg}", self::QUERY_FAILED);
    }

    static function not_connect_error($domain)
    {
        return new static("NOT_CONNECT: {$domain}", self::NOT_CONNECT);
    }

    static function del_new_instance_error($class_name)
    {
        return new static("DEL_NEW_INSTANCE: {$class_name}", self::DEL_NEW_INSTANCE);
    }

    static function composite_id_not_implemented_error($method)
    {
        return new static("COMPOSITE_ID_NOT_IMPLEMENTED: {$method}", self::COMPOSITE_ID_NOT_IMPLEMENTED);
    }

    static function unexpected_del_error($class_name, $result)
    {
        return new static("UNEXPECTED_DELETE: {$class_name}, {$result}", self::UNEXPECTED_DELETE);
    }
}

