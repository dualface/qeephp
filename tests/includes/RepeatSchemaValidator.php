<?php

namespace tests\includes;

class RepeatSchemaValidator
{
    /**
     * 验证 schmea 的对象
     *
     * @var SchemaValidator
     */
    private $_schema;

    /**
     * 验证结果行
     *
     * @var array
     */
    private $_resultLines = array();

    function __construct(array $schema, $verbose = false)
    {
        $this->_schema = new SchemaValidator($schema, $verbose);
    }

    /**
     * 对数据进行验证，返回验证结果
     *
     * @param array $arrData
     * @param int $indent
     *
     * @return bool
     */
    function validate(array $arrData, $indent = 0, array & $lines = null)
    {
        $this->_resultLines = array();
        if (is_null($lines))
        {
            $lines = $this->_resultLines;
        }
        $leader = str_repeat('    ', $indent);
        $passed = true;
        foreach ($arrData as $offset => $data)
        {
            if (!is_array($data))
            {
                $type = gettype($data);
                $lines[] = "{$leader}index {$offset}: [TYPE MISMATCH, expected is array, actual is {$type}]";
                $passed = false;
            }
            else
            {
                $lines[] = "{$leader}index {$offset}: {";
                if (!$this->_schema->validate($data, $indent + 1, $lines)) $passed = false;
                $lines[] = "{$leader}}";
            }
        }
        return $passed;
    }

    function getResultLines()
    {
        return $this->_resultLines;
    }

    function formatResult($indent)
    {
        $lines = array("{$indent}{");
        foreach ($lines as $offset => $result)
        {
            $lines[] = "{$indent}    {$offset}:{$result}";
        }
        $lines[] = "{$indent}}";
        return implode("\n", $lines);
    }
}

