<?php

namespace qeephp\storage\adapter;

use qeephp\storage\BaseModel;

class IStorageAdapterTool
{
    /**
     * @var IStorageAdapter
     */
    private $_storage;

    function __construct($storage)
    {
        $this->_storage = $storage;
    }

    /**
     * @return IStorageAdapter
     */
    function storage()
    {
        return $this->_storage;
    }

    /**
     * 检查指定的数据表是否存在
     *
     * @param string $collection
     *
     * @return boolean
     */
    function has_collection($collection)
    {
        return false;
    }

    /**
     * 删除指定的数据表
     *
     * @param string $collection
     *
     * @return IStorageAdapterTool
     */
    function drop_collection($collection)
    {

    }

    /**
     * 创建数据表
     *
     * @param string $collection
     * @param array $fields
     * @param array $options
     *
     * @return IStorageAdapterTool
     */
    function create_collection($collection, array $fields, array $options = array())
    {
        echo $collection;
        echo "\n";

        if (method_exists($this->_storage, 'current'))
        {
            $storage = $this->_storage->current();
        }
        else
        {
            $storage = $this->_storage;
        }

        $ddl = "CREATE TABLE " . $storage->id($collection) . "(\n";
        foreach ($fields as $field)
        {
            $params = array();
            $params[] = $storage->id($field['name']);
            switch ($field['type'])
            {
            case BaseModel::TYPE_SERIAL:
                $params[] = 'INT';
                $params[] = 'NOT NULL';
                $params[] = 'AUTO_INCREMENT';
                $params[] = 'PRIMARY KEY';
                break;

            case BaseModel::TYPE_INT:
                $params[] = 'INT';
                $params[] = 'NOT NULL';
                break;

            case BaseModel::TYPE_SMALLINT:
                $params[] = 'SMALLINT';
                $params[] = 'NOT NULL';
                break;

            case BaseModel::TYPE_BOOL:
                $params[] = 'BOOLEAN';
                $params[] = 'NOT NULL';
                break;

            case BaseModel::TYPE_FLOAT:
                $params[] = 'FLOAT';
                $params[] = 'NOT NULL';
                break;

            case BaseModel::TYPE_STRING:
                $params[] = 'TEXT';
                $params[] = 'NOT NULL';
                break;
            }

            $ddl .= '  ' . implode(' ', $params);
            $ddl .= ",\n";
        }

        echo $ddl;
//        CREATE TABLE  `test`.`ttt1` (
//`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
//`username` VARCHAR( 80 ) NOT NULL ,
//`password` VARCHAR( 80 ) NULL ,
//`email` TEXT NOT NULL ,
//`created` INT NOT NULL ,
//`isLocked` BOOLEAN NOT NULL DEFAULT  '1'
//);

    }

    /**
     * 调整数据表的字段
     *
     * @param string $collection
     * @param array $fields
     *
     * @return IStorageAdapterTool
     */
    function alter_collection_columns($collection, array $fields)
    {
        echo __METHOD__;
        echo "\n";

    }
}

