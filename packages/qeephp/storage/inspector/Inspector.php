<?php

namespace qeephp\storage\inspector;

use qeephp\storage\IStorageDefine;
use qeephp\storage\Meta;
use qeephp\errors\ModelError;

/**
 * Inspector 用于分析一个模型类
 */
abstract class Inspector implements IStorageDefine
{
    static $default_class_data = array(
        'domain'            => null,
        'collection'        => null,
        'idname'            => null,
        'autoincr_idname'   => null,
        'composite_id'      => false,
        'update'            => 'changed',
        'extends'           => null,
        'readonly'          => false,
        'nonp'              => false,
        'bind'              => array(),
        'props'             => array(),
    );

    static $default_prop_meta = array(
        'name'              => null,
        'type'              => self::TYPE_STRING,
        'len'               => null,
        'default'           => null,
        'id'                => false,
        'autoincr'          => false,
        'optional'          => false,
        'nonp'              => false,
        'field'             => null,
        'readonly'          => false,
        'getter'            => null,
        'setter'            => null,
        'update'            => 'overwrite',
    );

    static $update_policy_map = array(
        'all'               => self::UPDATE_ALL_PROPS,
        'changed'           => self::UPDATE_CHANGED_PROPS,
        'check_all'         => self::UPDATE_CHECK_ALL,
        'check_changed'     => self::UPDATE_CHECK_CHANGED,
        'check_non'         => self::UPDATE_CHECK_NON,
    );

    static $upadte_prop_policy_map = array(
        'overwrite'         => self::UPDATE_PROP_OVERWRITE,
        'incr'              => self::UPDATE_PROP_INCR,
        'gt_zero'           => self::UPDATE_PROP_GT_ZERO,
        'gte_zero'          => self::UPDATE_PROP_GTE_ZERO,
        'ignore'            => self::UPDATE_PROP_IGNORE,
    );

    /**
     * 通过反射获取模型类的信息
     *
     * @param string $model_class_name
     *
     * @return array
     */
    static function inspect($model_class_name)
    {
        $class = new \ReflectionClass($model_class_name);
        $parent_class = $class->getParentClass();
        /* @var $parent_class \ReflectionClass */
        if ($parent_class && $parent_class->getName() != 'qeephp\\storage\\BaseModel')
        {
            $data = self::inspect($parent_class->getName());
            $data = self::_inspect_class($class, $data);
        }
        else
        {
            $data = self::_inspect_class($class);
            self::_finalize_class_data($model_class_name, $data);
            self::_finalize_props_data($model_class_name, $data);
        }
        return $data;
    }

    private static function _inspect_class(\ReflectionClass $class, array $parent_data = null)
    {
        if (empty($parent_data)) $parent_data = self::$default_class_data;
        $data = DocComment::parse($class->getDocComment());

        // 父类优先的设定
        $keys = array('domain', 'collection', 'idname', 'id_autoincr', 'extends');
        foreach ($keys as $key)
        {
            if (!empty($parent_data[$key]))
            {
                $data[$key] = $parent_data[$key];
            }
        }

        // 继承类优先的设定
        $data['readonly'] = !isset($data['readonly']) ? $parent_data['readonly'] : true;
        $data['nonp'] = !isset($data['nonp']) ? $parent_data['nonp'] : true;
        if (!isset($data['update']))
        {
            $data['update'] = $parent_data['update'];
        }

        // 可以合并的属性
        if (empty($data['bind']))
        {
            $data['bind'] = $parent_data['bind'];
        }
        else if (!empty($parent_data['bind']))
        {
            $data['bind'] += $parent_data['bind'];
        }

        // 属性的设定
        $props = self::_inspect_props($class);
        if (!empty($parent_data['props']))
        {
            $keys = array('name', 'type', 'id', 'autoincr', 'field');
            foreach ($parent_data['props'] as $name => $prop)
            {
                if (!isset($props[$name]))
                {
                    $props[$name] = $prop;
                }
                else
                {
                    // 对于已经在父类中定义的属性，部分设定以父类优先
                    foreach ($keys as $key)
                    {
                        $props[$name][$key] = $prop[$key];
                    }
                }
            }
        }
        $data['props'] = $props;

        // extends
        if (empty($data['extends']))
        {
            $data['extends'] = $parent_data['extends'];
        }
        else if (!is_array($data['extends'])
                || empty($data['extends']['by'])
                || empty($data['extends']['classes']))
        {
            throw ModelError::invalid_config_error($class->getName(), 'extends');
        }
        else
        {
            $arr = arr($data['extends']['classes']);
            $classes = array();
            $offset = 1;
            foreach ($arr as $name)
            {
                if (strpos($name, '='))
                {
                    list($name, $offset) = arr($name, '=');
                }
                $classes[$offset] = $name;
                $offset++;
            }
            $data['extends']['classes'] = $classes;
        }

        return $data;
    }

    /**
     * 取得指定类所有属性的设定
     *
     * @param \ReflectionClass $class
     *
     * @return array
     */
    private static function _inspect_props(\ReflectionClass $class)
    {
        $props = array();
        foreach ($class->getProperties() as $prop_reflection)
        {
            /* @var $prop_reflection \ReflectionProperty */
            if ($prop_reflection->isStatic()
                || !$prop_reflection->isPublic()
                || $prop_reflection->getDeclaringClass() != $class) continue;

            $prop = self::_inspect_prop($prop_reflection);
            if ($prop)
            {
                $props[$prop_reflection->getName()] = $prop;
            }
        }
        return $props;
    }

    /**
     * 取得指定属性的设定
     *
     * @param ReflectionProperty $prop_reflection
     *
     * @return array
     */
    private static function _inspect_prop(\ReflectionProperty $prop_reflection)
    {
        $name = $prop_reflection->getName();
        $prop = self::$default_prop_meta;
        $doc = DocComment::parse($prop_reflection->getDocComment());
        foreach ($doc as $key => $text)
        {
            switch ($key)
            {
            case 'internal':
                return false;

            case 'id':
                $prop['id'] = true;
                if (strcasecmp($text, 'autoincr') == 0)
                {
                    $prop['autoincr'] = true;
                }
                break;

            case 'var':
                if (strpos($text, '(') !== false)
                {
                    list($text, $len) = arr(rtrim($text, ')'), '(');
                }
                else
                {
                    $len = 200;
                }
                $text = strtolower($text);
                $prop['type'] = isset(Meta::$type_map[$text])
                                ? Meta::$type_map[$text]
                                : self::TYPE_STRING;
                if ($prop['type'] == self::TYPE_SERIAL)
                {
                    $prop['id'] = true;
                    $prop['autoincr'] = true;
                }
                if ($prop['type'] == self::TYPE_STRING)
                {
                    $prop['len'] = $len;
                }
                break;

            case 'update':
                $text = strtolower($text);
                if (isset(self::$upadte_prop_policy_map[$text]))
                {
                    $prop['update'] = self::$upadte_prop_policy_map[$text];
                }
                break;

            case 'nonp':
            case 'readonly':
            case 'optional':
                $prop[$key] = true;
                break;

            default:
                $prop[$key] = $text;
            }
        }

        if (empty($prop['field']))
        {
            $prop['field'] = $name;
        }
        $prop['name'] = $name;
        return $prop;
    }

    private static function _finalize_class_data($class, array & $data)
    {
        $values = arr(strtolower($data['update']), '|');
        $data['update'] = 0;
        foreach ($values as $value)
        {
            if (isset(self::$update_policy_map[$value]))
            {
                $data['update'] |= self::$update_policy_map[$value];
            }
        }
        if (($data['update'] & (self::UPDATE_ALL_PROPS | self::UPDATE_CHANGED_PROPS)) == 0)
        {
            $data['update'] |= self::UPDATE_CHANGED_PROPS;
        }
    }

    private static function _finalize_props_data($class, array & $data)
    {
        $data['props_to_fields'] = array();
        $data['fields_to_props'] = array();
        $data['spec_update_props'] = array();
        foreach ($data['props'] as $name => $prop)
        {
            if ($prop['nonp']) continue;
            $data['props_to_fields'][$name] = $prop['field'];
            $data['fields_to_props'][$prop['field']] = $name;
            if ($prop['update'] != self::UPDATE_PROP_OVERWRITE)
            {
                $data['spec_update_props'][$name] = $prop['update'];
            }

            if (!$prop['id']) continue;
            if (empty($data['idname']))
            {
                $data['idname'] = $name;
                if ($prop['autoincr']) $data['autoincr_idname'] = $name;
            }
            else
            {
                if (!is_array($data['idname'])) $data['idname'] = array($data['idname']);
                $data['idname'][] = $name;
                if ($prop['autoincr'])
                {
                    if ($data['autoincr_idname'])
                    {
                        throw ModelError::invalid_config_error($class, 'autoincr_idname');
                    }
                    $data['autoincr_idname'] = $name;
                }
            }
        }

        if (is_array($data['idname'])) $data['composite_id'] = true;

        $defaults = get_class_vars($class);
        foreach ($defaults as $name => $value)
        {
            if (isset($data['props'][$name]))
            {
                $data['props'][$name]['default'] = $value;
            }
        }
    }
}

