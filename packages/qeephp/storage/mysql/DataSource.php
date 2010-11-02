<?php

namespace qeephp\storage\mysql;

use qeephp\tools\ILogger;
use qeephp\storage\IDataSource;
use qeephp\storage\IModel;
use qeephp\storage\BaseModel;
use qeephp\storage\Meta;
use qeephp\storage\Expr;
use qeephp\storage\StorageError;

class DataSource implements IDataSource, IModel
{
    /**
     * 配置
     *
     * @var array
     */
    public $config;

    /**
     * 查询累计次数
     *
     * @var int
     */
    public $query_count = 0;

    /**
     * 连接 MySQL 数据库后获得的资源句柄
     *
     * @var resource
     */
    private $_handle;

    /**
     * 是否是持久连接
     *
     * @var bool
     */
    private $_is_persistent = false;

    /**
     * @var ILogger
     */
    private $_logger;

    function __construct(array $config)
    {
        $this->config = $config;
    }

    function set_logger(ILogger $logger)
    {
        $this->_logger = $logger;
    }

    function remove_logger()
    {
        $this->_logger = $logger;
    }

    /**
     * 确定是否已经连接到 MySQL 数据库
     *
     * @return bool
     */
    function is_connected()
    {
        return !empty($this->_handle);
    }

    /**
     * 返回连接 MySQL 数据库后获得的资源句柄，如果还未连接数据库则抛出异常
     *
     * @return resource
     */
    function handle()
    {
        if (!$this->_handle)
        {
            throw StorageError::not_connect_error($this->config['database']);
        }
        return $this->_handle;
    }

    /**
     * 连接到数据库
     *
     * @param bool $persistent 是否创建持久化连接
     */
    function connect($persistent = false)
    {
        if ($this->is_connected()) return;
        $config = $this->config;
        $this->_is_persistent = val($config, 'persistent', $persistent);
        if ($this->_is_persistent)
        {
            $this->_handle = mysql_pconnect($config['host'], $config['login'], $config['password']);
        }
        else
        {
            $this->_handle = mysql_connect($config['host'], $config['login'], $config['password']);
        }
        if (!$this->_handle)
        {
            throw StorageError::connect_failed_error(mysql_errno(), mysql_error(), $config['database']);
        }
        mysql_select_db($config['database']);
        mysql_set_charset(val($this->config, 'encoding', 'utf8'));
        $this->query_count = 0;
    }

    /**
     * 关闭连接并清理资源，如果是持久连接则忽略该调用
     */
    function close()
    {
        if ($this->_handle && !$this->_is_persistent)
        {
            mysql_close($this->_handle);
            $this->_handle = null;
            $this->query_count = 0;
        }
    }

    function find_one($table, $cond, $fields = null, array $alias = null)
    {
        $result = $this->select($table, $cond, $fields, null, 0, 1, $alias);
        if (!$result) return false;
        $row = mysql_fetch_assoc($result);
        mysql_free_result($result);
        return $row;
    }

    function find($table, $cond, $fields = null, array $alias = null)
    {
        return new Finder($this, $table, $cond, $fields, $alias);
    }

    function insert($table, array $values, array $alias = null)
    {
        $table = $this->id($table);
        $keys = array();
        $fields = array();
        foreach ($values as $field => $field_value)
        {
            if (isset($alias[$field])) $field = $alias[$field];
            $keys[] = $this->id($field);
            $fields[] = _mysql_escape($field_value);
        }

        $sql = "INSERT INTO {$table} ("
               . implode(',', $keys) . ') VALUES ('
               . implode(',', $fields) . ')';
        $this->execute($sql);
        return mysql_insert_id($this->_handle);
    }

    function update($table, $cond, $values, array $alias = null)
    {
        $table = $this->id($table);

        if (is_array($values))
        {
            $fields = array();
            foreach ($values as $field => $field_value)
            {
                if (is_string($field))
                {
                    if (isset($alias[$field])) $field = $alias[$field];
                    if ($field_value instanceof Expr)
                    {
                        $fields[] = $this->id($field) . ' = ' . _mysql_format_expr($field_value, $alias);
                    }
                    else
                    {
                        $fields[] = $this->id($field) . ' = ' . _mysql_escape($field_value);
                    }
                }
                else
                {
                    $fields[] = _mysql_format_alias($field_value, $alias);
                }
            }
            $sql = "UPDATE {$table} SET " . implode(', ', $fields);
        }
        else
        {
            $values = _mysql_format_alias($values, $alias);
            $sql = "UPDATE {$table} SET {$values}";
        }
        list($where, $args) = $this->format_cond($cond, $alias);
        if ($where) $sql .= " WHERE {$where}";

        $this->execute($sql, $args);
        return mysql_affected_rows($this->_handle);
    }

    function update_model(BaseModel $model, Meta $meta)
    {
        if ($meta->update & self::UPDATE_ALL_PROPS)
        {
            $changes = $model->__to_array();
        }
        else
        {
            $changes = $model->changes();
        }
        foreach ((array)$meta->idname as $name)
        {
            unset($changes[$name]);
        }

        $origin = $model->origin();
        $update_cond = array();
        if (($meta->update & self::UPDATE_CHECK_ALL)
            || ($meta->update & self::UPDATE_CHECK_CHANGED))
        {
            $update_cond = $origin;
            if ($meta->update & self::UPDATE_CHECK_CHANGED)
            {
                $u = $model->changes();
                foreach ($u as $name => $value)
                {
                    $u[$name] = $update_cond[$name];
                }
                $update_cond = $u;
            }
        }
        if ($meta->composite_id)
        {
            foreach ($meta->idname as $name)
            {
                $update_cond[$name] = $model->$name;
            }
        }
        else
        {
            $update_cond[$meta->idname] = $model->id();
        }

        $changes = $meta->props_to_fields($changes);
        $update_cond = $meta->props_to_fields($update_cond);

        foreach ($meta->spec_update_props as $name => $policy)
        {
            if ($policy == IModel::UPDATE_PROP_IGNORE)
            {
                unset($changes[$name]);
                unset($update_cond[$name]);
                continue;
            }
            else if (!isset($changes[$name]) && !isset($update_cond[$name]))
            {
                continue;
            }

            unset($update_cond[$name]);
            switch ($policy)
            {
            case IModel::UPDATE_PROP_INCR:
                $changes[$name] = new Expr("[{$name}] + ?", $changes[$name] - $origin[$name]);
                break;
            case IModel::UPDATE_PROP_GTE_ZERO:
            case IModel::UPDATE_PROP_GT_ZERO:
                if ($origin[$name] < $changes[$name])
                {
                    $changes[$name] = new Expr("[{$name}] + ?", $changes[$name] - $origin[$name]);
                }
                else
                {
                    $offset = $origin[$name] - $changes[$name];
                    $changes[$name] = new Expr("[{$name}] - ?", $offset);
                    $op = ($policy == IModel::UPDATE_PROP_GTE_ZERO) ? '>=' : '>';
                    $update_cond[] = new Expr("[{$name}] {$op} ?", $offset);
                }
            }
        }

        $result = $this->update($meta->collection(),
                                $update_cond,
                                $changes,
                                $meta->props_to_fields);
        return $result > 0;
    }

    function del($table, $cond, array $alias = null)
    {
        $table = $this->id($table);
        $sql = "DELETE FROM {$table}";
        list($where, $args) = $this->format_cond($cond, $alias);
        if ($where) $sql .= " WHERE {$where}";
        $this->execute($sql, $args);
        return mysql_affected_rows($this->_handle);
    }

    /**
     * 执行 SELECT 查询，并返回资源句柄或查询是否成功的结果
     *
     * @param string $table
     * @param mixed $cond
     * @param mixed $fields
     * @param mixed $sort
     * @param mixed $skip
     * @param mixed $limit
     * @param array $alias
     *
     * @return resource|bool
     */
    function select($table,
                    $cond,
                    $fields = null,
                    $sort = null,
                    $skip = null,
                    $limit = null,
                    array $alias = null)
    {
        $fields = $this->format_fields($fields, $alias);
        $table = $this->id($table);
        $sql = "SELECT {$fields} FROM {$table}";
        list($where, $args) = $this->format_cond($cond, $alias);
        if ($where) $sql .= " WHERE {$where}";

        if (!is_null($skip) || !is_null($limit))
        {
            $skip = intval($skip);
            $limit = intval($limit);
            $sql .= " LIMIT {$skip}, {$limit}";
        }

        return $this->execute($sql, $args);
    }

    /**
     * 开启事务
     */
    function begin()
    {
        $this->execute('SET AUTOCOMMIT=0');
        $this->execute('START TRANSACTION');
    }

    /**
     * 提交事务
     */
    function commit()
    {
        $this->execute('COMMIT');
        $this->execute('SET AUTOCOMMIT=1');
    }

    /**
     * 回滚事务
     */
    function rollback()
    {
        $this->execute('ROLLBACK');
        $this->execute('SET AUTOCOMMIT=1');
    }

    /**
     * 执行 SQL 查询
     *
     * @param string $sql
     * @param array $args
     *
     * @return mixed
     */
    function execute($sql, array $args = null)
    {
        if (is_array($sql))
        {
            $args = $sql;
            $sql = array_shift($args);
        }
        $sql = _mysql_format_sql_with_args($sql, $args);

        if (!$this->is_connected()) $this->connect();
        $result = mysql_query($sql, $this->_handle);

        if ($this->_logger)
        {
            $this->_logger->log(($result) ? ILogger::DEBUG : ILogger::ERROR, "DataSource: {$sql}");
        }

        $this->query_count++;
        if ($result !== false) return $result;

        throw StorageError::query_failed_error(mysql_errno($this->_handle),
                                              mysql_error($this->_handle) . ', ' . $sql,
                                              $this->config['database']);
    }

    /**
     * 转义值
     *
     * @param mixed $value
     *
     * @return string
     */
    function escape($value)
    {
        return _mysql_escape($value);
    }

    /**
     * 转义标识符
     *
     * @param string $name
     *
     * @return string
     */
    function id($name)
    {
        return _mysql_id($name);
    }

    /**
     * 格式化字段名
     *
     * @param string $fields
     * @param array $alias
     *
     * @return string
     */
    function format_fields($fields, array $alias = null)
    {
        if (!$fields)
        {
            $fields = '*';
        }
        else
        {
            $fields = arr($fields, ',');
            foreach ($fields as $index => $field)
            {
                $fields[$index] = $this->id($field);
            }
            $fields = implode(',', $fields);
            if ($alias) $fields = _mysql_format_alias($fields, $alias);
        }
        return $fields;
    }

    /**
     * 格式化查询条件
     *
     * @param mixed $cond
     * @param array $alias
     *
     * @return array
     */
    function format_cond($cond, array $alias = null)
    {
        if (is_string($cond))
        {
            if ($alias) $cond = _mysql_format_alias ($cond, $alias);
            return array($cond, null);
        }
        if (!is_array($cond)) return array(false, null);

        reset($cond);
        if (is_int(key($cond)))
        {
            // array(query_cond, query_parameters)
            $query_cond = (string)array_shift($cond);
            if ($alias) $query_cond = _mysql_format_alias($query_cond, $alias);
            return array($query_cond, $cond);
        }
        else
        {
            // array(field => value, ...)
            $where = array();
            foreach ($cond as $field => $value)
            {
                if (isset($alias[$field])) $field = $alias[$field];
                if (is_array($value))
                {
                    $where[] = $this->id($field) . ' IN (' . _mysql_escape($value) . ')';
                }
                else
                {
                    $where[] = $this->id($field) . ' = ' . _mysql_escape($value);
                }
            }
            return array(implode(' AND ', $where), null);
        }
    }
}

function _mysql_id($name)
{
    $arr = arr(str_replace('`', '', $name), '.');
    foreach ($arr as $k => $v)
    {
        $arr[$k] = ($v != '*') ? "`{$v}`" : '*';
    }
    return implode('.', $arr);
}

function _mysql_escape($value)
{
    if (is_int($value) || is_float($value)) return $value;
    if (is_null($value)) return 'NULL';
    if (is_bool($value)) return ($value) ? 'TRUE' : 'FALSE';
    if (is_array($value))
    {
        return implode(',', array_map('qeephp\\storage\\mysql\\_mysql_escape', $value));
    }
    return "'" . addslashes($value) . "'";
}

function _mysql_format_alias($string, array $alias = null)
{
    if (!is_array($alias)) return $string;
    $rep = function ($part) use ($alias) {
        $part = $part[1];
        $part = isset($alias[$part]) ? $alias[$part] : $part;
        return _mysql_id($part);
    };
    return preg_replace_callback('/\[([a-z_0-9]+)\]/i', $rep, $string);
}

function _mysql_format_expr(Expr $expr, $alias)
{
    $args = $expr->expr;
    $sql = array_shift($args);
    return _mysql_format_alias(_mysql_format_sql_with_args($sql, $args), $alias);
}

function _mysql_format_sql_with_args($sql, array $args = null)
{
    if (!empty($args))
    {
        $arr = explode('?', $sql);
        $sql = array_shift($arr);
        foreach ($args as $value)
        {
            if (isset($arr[0]))
            {
                $sql .= _mysql_escape($value) . array_shift($arr);
            }
        }
    }
    return $sql;
}


