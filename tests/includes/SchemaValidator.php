<?php

namespace tests\includes;

use qeephp\debug\DebugDump;

class SchemaValidator
{
    /**
     * 要验证的 schema 定义
     *
     * @var array
     */
    private $_schema;

    /**
     * 验证结果输出行
     *
     * @var array
     */
    private $_resultLines;

    /**
     * 验证结果
     *
     * @var bool
     */
    private $_passed;

    /**
     * 是否完整输出所有的验证过程
     *
     * @var bool
     */
    private $_verbose = false;

    /**
     * 最初的缩进
     *
     * @var int
     */
    private $_rootIndent = 0;

    /**
     * 用于输出数据的 DebugDump 对象实例
     *
     * @var DebugDump
     */
    private static $_dump;

    /**
     * 构造函数
     *
     * @param array $schema
     * @param bool $verbose
     */
    function __construct(array $schema, $verbose = false)
    {
        $this->_schema = $schema;
        $this->_verbose = $verbose;
        if (!self::$_dump)
        {
            self::$_dump = new DebugDump(false, 1);
        }
    }

    /**
     * 对数据进行验证，并返回验证结果
     *
     * @param array $arrData
     * @param int $indent
     * @param array $lines
     *
     * @return bool
     */
    function validate(array $arrData, $indent = 0, array & $lines = null)
    {
        $this->_rootIndent = $indent;
        $this->_passed = true;
        $this->_resultLines = array();
        if (is_null($lines))
        {
            $lines =& $this->_resultLines;
        }
        $this->_validateSchema($arrData, $this->_schema, $indent, $lines);
        return $this->_passed;
    }

    /**
     * 取得验证结果的文本行
     *
     * @return array
     */
    function getResultLines()
    {
        return $this->_resultLines;
    }

    /**
     * 以字符串格式输出对象内容
     *
     * @return string
     */
    function __toString()
    {
        if ($this->_rootIndent == 0)
        {
            if ($this->_passed) return 'PASSED';
            
            $separtor = str_repeat('-', 40);
            $leader = str_repeat(' ', 9);
            $text = implode("\n{$leader}", $this->_resultLines);
            $text = "FAILED\n{$leader}{$separtor}\n{$leader}{$text}\n{$leader}{$separtor}\n";
        }
        else
        {
            $text = implode("\n", $this->_resultLines);
        }
        return $text;
    }

    private function _validateSchema(array $arrData, array $schema, $indent, array & $lines)
    {
        foreach ($schema as $keyName => $type)
        {
            $leader = str_repeat('    ', $indent);
            if (!array_key_exists($keyName, $arrData))
            {
                $lines[] = "{$leader}- {$keyName}: [MISS KEY]";
                $this->_passed = false;
            }
            else
            {
                $data = $arrData[$keyName];
                unset($arrData[$keyName]);
                $this->_validateByData($keyName, $data, $type, $indent, $lines);
            }
        }

        $more = array_keys($arrData);
        if ($more)
        {
            $lines[] = '';
            $lines[] = "{$leader}-- MORE KEYS --";
            $this->_passed = false;
            $leader = str_repeat('    ', $indent);
            foreach ($more as $keyName)
            {
                $lines[] = "{$leader}+ {$keyName}: " . self::_dumpValue($arrData[$keyName]);
            }
        }
    }

    private function _validateByData($keyName, $data, $type, $indent, array & $lines)
    {
        $leader = str_repeat('    ', $indent);

        if (is_array($type))
        {
            $lines[] = "{$leader}{$keyName}: {";
            $this->_validateSchema($data, $type, $indent + 1, $lines);
            $lines[] = "{$leader}}";
        }
        else if (is_object($type) && $type instanceof RepeatSchemaValidator)
        {
            $lines[] = "{$leader}{$keyName}: {";
            /* @var $type RepeatSchemaValidator */
            if (!$type->validate($data, $indent + 1, $lines))
            {
                $this->_passed = false;
            }
            $lines[] = "{$leader}}";
        }
        else
        {
            $actualType = gettype($data);
            if (strcasecmp($actualType, $type) == 0)
            {
                $lines[] = "{$leader}{$keyName}: " . self::_dumpValue($data);
            }
            else
            {
                $this->_passed = false;
                $lines[] = "{$leader}* {$keyName}: [TYPE MISMATCH, expected is {$type}, actual is {$actualType}]";
            }
        }
    }

    private function _dumpValue($value)
    {
        if (is_array($value) || is_object($value))
        {
            return '<' . gettype($value) . '>';
        }
        else
        {
            return self::$_dump->escape($value);
        }
    }
}
