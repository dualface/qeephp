<?php

namespace qeephp\debug;

/**
 * 输出格式化以后的数据
 */
class DebugDump
{
    public $html;
    public $stack = array();
    public $max_dump_depth = 3;
    public $html_array = '<span style="color: #c11; font-weight: bold;">%s</span>';
    public $html_obj = '<span style="color: #661; font-weight: bold; text-decoration: underline;">%s</span>';
    public $html_gray = '<span style="color: #999;">%s</span>';
    public $html_key = '<span style="color: #116;">%s</span>';
    public $html_prop = '<span style="color: #33f; font-weight: bold;">%s</span>';
    public $html_number = '<span style="color: #4e9a06;">%s</span>';
    public $html_bool = '<span style="color: #75507b; font-weight: bold;">%s</span>';
    public $html_null = '<span style="color: #3465a4; font-weight: bold;">%s</span>';
    public $html_string = '<span style="color: #f57900;">%s</span>';
    public $html_warn = '<span style="color: #611;">%s</span>';

    /**
     * 构造函数
     *
     * @param bool $html 是否使用 html 格式
     * @param int $depth 输出多少层数据，默认为 3 层
     */
    function __construct($html = null, $depth = 3)
    {
        $this->html = (is_null($html))
                      ? ini_get('html_errors')
                      : $html;
        if ($depth > 0)
        {
            $this->max_dump_depth = $depth;
        }
    }

    /**
     * 将一个变量转义为输出
     *
     * @param mixed $object
     * @param int $depth
     *
     * @return string
     */
    function escape($object, $depth = 1)
    {
        if (is_array($object))
        {
            return $this->escape_array($object, $depth);
        }
        elseif (is_resource($object))
        {
            return $this->escape_resource($object);
        }
        elseif (!is_object($object))
        {
            return $this->escape_value($object);
        }

        foreach ($this->stack as $ref)
        {
            if ($ref === $object)
            {
                if ($this->html)
                {
                    return '** ' . sprintf($this->html_warn, 'Recursion')
                           . ' Object(' . $this->_make_object_link($object) . ') **';
                }
                else
                {
                    return '** Object(' . get_class($object) . ') **';
                }
            }
        }

        if ($depth > $this->max_dump_depth)
        {
            if ($this->html)
            {
                return '** ' . sprintf($this->html_warn, 'Max Dump Depth')
                       . ' Object(' . $this->_make_object_link($object) . ') **';
            }
            else
            {
                return '** Object(' . get_class($object) . ') **';
            }
        }

        array_push($this->stack, $object);

        if ($object instanceof \Exception)
        {
            return $this->escape_exception($object, $depth);
        }

        $class = get_class($object);
        $class_reflection = new \ReflectionClass($class);
        $props = array();
        foreach ($class_reflection->getProperties() as $prop_ref)
        {
            $props[$prop_ref->getName()] = $prop_ref;
        }

        $spc = str_repeat('  ', $depth);
        $return = array();
        if ($this->html)
        {
            $return[] = '<a name="Class_' . htmlentities($class) . '"></a>object('
                        . $this->_make_object_link($object) . ') {';
        }
        else
        {
            $return[] = "object({$class}) {";
        }

        $members = (array)$object;
        foreach ($props as $raw_name => $prop_ref)
        {
            $name = $raw_name;
            $return[] = $spc . $this->_escape_prop($object, $prop_ref, $members, $depth);
        }

        foreach ($members as $raw_name => $value)
        {
            $name = $raw_name;

            if ($name[0] == "\0")
            {
                $parts = explode("\0", $name);
                $name = $parts[2];
            }
            if (isset($props[$name])) continue;

            try
            {
                $prop_ref = new \ReflectionProperty($object, $name);
                $return[] = $spc . $this->_escape_prop($object, $prop_ref, $members, $depth);
            }
            catch (\Exception $ex)
            {
                if ($this->html)
                {
                    $return[] = $spc . sprintf($this->html_gray, 'private') . ' '
                                . "'" . sprintf($this->html_prop, $name) . "' "
                                . sprintf($this->html_gray, '=&gt;') . ' '
                                . $this->escape($value, $depth + 1);
                }
                else
                {
                    $return[] = "{$spc}private '{$name}' => " . $this->escape($value, $depth + 1);
                }
                unset($ex);
            }
        }

        $spc = substr($spc, 2);
        $return[] = "{$spc}}";
        $return[] = '';

        return implode("\n", $return);
    }

    /**
     * 转义异常
     *
     * @param Exception $ex
     *
     * @return string
     */
    function escape_exception(\Exception $ex)
    {
        $out = "exception '" . get_class($ex) . "'";
        if ($ex->getMessage() != '')
        {
            $out .= " with message '" . $ex->getMessage() . "'";
        }

        $out .= ' in ' . $ex->getFile() . ':' . $ex->getLine() . "\n\n";
        $out .= $ex->getTraceAsString();
        return $out;
    }

    /**
     * 转义资源
     *
     * @param resource $resource
     *
     * @return string
     */
    function escape_resource($resource)
    {
        if ($this->html)
        {
            return sprintf($this->html_gray, '**') . ' '
                   . sprintf($this->html_obj, h((string)$resource))
                   . ' ' . sprintf($this->html_gray, '**');
        }
        else
        {
            return '** ' . (string)$resource . ' **';
        }
    }

    /**
     * 转义一个数组
     *
     * @param array $arr
     * @param int $depth
     *
     * @return string
     */
    function escape_array(array $arr, $depth = 1)
    {
        $spc = str_repeat('  ', $depth);
        if ($this->html)
        {
            $return = sprintf($this->html_array, 'array(');
        }
        else
        {
            $return = 'array(';
        }

        if ($depth > $this->max_dump_depth)
        {
            if ($this->html)
            {
                $return .= ' ** ' . sprintf($this->html_warn, 'Max Dump Depth') . ' ** ';
            }
            else
            {
                $return .= ' ** Max Dump Depth ** ';
            }
        }
        elseif (empty($arr))
        {
            if ($this->html)
            {
                $return .= ' ' . sprintf($this->html_gray, 'empty') . ' ';
            }
            else
            {
                $return .= ' empty ';
            }
        }
        else
        {
            $return .= "\n";
            foreach ($arr as $key => $value)
            {
                $return .= $spc;
                if ($this->html)
                {
                    if (is_int($key) || is_double($key))
                    {
                        $return .= sprintf($this->html_number, h($key))
                                   . ' ' . sprintf($this->html_gray, '=&gt;') . ' ';
                    }
                    else
                    {
                        $return .= "'" . sprintf($this->html_key, h($key)) . "'"
                                   . ' ' . sprintf($this->html_gray, '=&gt;') . ' ';
                    }
                }
                else
                {
                    if (is_int($key) || is_double($key))
                    {
                        $return .= "{$key} => ";
                    }
                    else
                    {
                        $return .= "'{$key}' => ";
                    }
                }
                $return .= $this->escape($value, $depth + 1) . "\n";
            }
            $spc = substr($spc, 2);
            $return .= "{$spc}";
        }

        if ($this->html)
        {
            $return .= sprintf($this->html_array, ')');
        }
        else
        {
            $return .= ')';
        }

        return $return;
    }

    /**
     * 转义一个值
     *
     * @param mixed $value
     *
     * @return string
     */
    function escape_value($value)
    {
        $type = gettype($value);
        switch ($type)
        {
        case 'boolean':
            $value = ($value) ? 'TRUE' : 'FALSE';
            $return = ($this->html ? sprintf($this->html_bool, $value) : $value);
            break;

        case 'integer':
        case 'double':
            $return = ($type == 'integer') ? 'int' : 'float';
            $return .= ' ' . ($this->html ? sprintf($this->html_number, $value) : $value);
            break;

        case 'NULL':
            $return = ($this->html ? sprintf($this->html_null, 'NULL') : 'NULL');
            break;

        case 'string':
            $return = "'" . ($this->html ? sprintf($this->html_string, $value) : $value) . "'";
            $return .= ' (len=' . strlen($value) . ')';
            break;

        default:
            $return = $value;
            break;
        }

        return $return;
    }

    /**
     * 转义一个对象属性
     *
     * @param object $object
     * @param \ReflectionProperty $prop_ref
     * @param array $members
     * @param int $depth
     *
     * @return string
     */
    private function _escape_prop($object, \ReflectionProperty $prop_ref, $members, $depth)
    {
        $name = $raw_name = $prop_ref->getName();
        $class = get_class($object);
        $prefix = '';
        if ($prop_ref->isPublic())
        {
            $prefix = 'public';
        }
        elseif ($prop_ref->isPrivate())
        {
            $prefix = 'private';
            $raw_name = "\0" . $class . "\0" . $raw_name;
        }
        elseif ($prop_ref->isProtected())
        {
            $prefix = 'protected';
            $raw_name = "\0" . '*' . "\0" . $raw_name;
        }
        if ($prop_ref->isStatic())
        {
            $prefix = 'static ' . $prefix;
        }

        if ($this->html)
        {
            $name = sprintf($this->html_gray, $prefix) . ' ' . sprintf($this->html_prop, h($name));
        }
        else
        {
            $name = "{$prefix} {$name}";
        }

        if (array_key_exists($raw_name, $members))
        {
            $text = $this->escape($members[$raw_name], $depth + 1);
        }
        elseif (method_exists($prop_ref, 'setAccessible'))
        {
            $prop_ref->setAccessible(true);
            $text = $this->escape($prop_ref->getValue($object), $depth + 1);
        }
        elseif ($prop_ref->isPublic())
        {
            $text = $this->escape($prop_ref->getValue($object), $depth + 1);
        }
        else
        {
            $text = '** Need PHP 5.3 to get value **';
        }

        if ($this->html)
        {
            return "{$name} " . sprintf($this->html_gray, '=') . " {$text}";
        }
        else
        {
            return "{$name} = {$text}";
        }
    }

    /**
     * 构造指向对象名称的连接
     *
     * @param object $object
     *
     * @return string
     */
    private function _make_object_link($object)
    {
        $class = h(get_class($object));
        return "<a href=\"#Class_{$class}\">" . sprintf($this->html_obj, $class) . '</a>';
    }
}

