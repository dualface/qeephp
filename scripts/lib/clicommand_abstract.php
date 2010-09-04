<?php

abstract class CliCommand_Abstract
{
    /**
     * 命令行参数
     *
     * @var array
     */
    protected $_argv;

    /**
     * 命令行参数的分析模式
     *
     * @var array
     */
    protected $_argv_pattern = array();

    /**
     * 构造函数
     */
    function __construct(array $argv = array())
    {
        $this->_argv = $this->_parseArgv($argv, $this->_argv_pattern);
    }

    /**
     * 执行生成器
     */
    abstract function execute();

    /**
     * 分析命令行参数
     *
     * @param array $argv
     * @param arary $pattern
     *
     * @return array
     */
    protected function _parseArgv(array $argv, array $pattern)
    {
        // 分解命令行参数模式
        $vars = array();
        $switches = array();
        $indexs = array();
        $offset = 0;
        foreach ($pattern as $name)
        {
            $p = $name{0};
            if ($p == '?' || $p == '*')
            {
                $name = substr($name, 1);
                $optional = true;
                $multi = ($p == '*');
            }
            else
            {
                $optional = false;
                $multi = false;
            }

            $arr = explode('|', $name);
            $name = array_shift($arr);
            $prefix = array_shift($arr);

            $vars[$name] = array(
                'name'      => $name,
                'optional'  => $optional, 
                'multi'     => $multi,
            );
            if ($prefix)
            {
                $switches[$prefix] = $name;
            }
            else
            {
                $indexs[$offset] = $name;
                $offset++;
            }
        }

        $result = array();
        $result['script'] = array_shift($argv);
        $offset = 0;
        while ($arg = array_shift($argv))
        {
            if (isset($switches[$arg]))
            {
                $name = $switches[$arg];
                $arg = array_shift($argv);
                if ($vars[$name]['multi'])
                {
                    $result[$name][] = $arg;
                }
                else
                {
                    $result[$name] = $arg;
                }
            }
            else
            {
                if (isset($indexs[$offset]))
                {
                    $result[$indexs[$offset]] = $arg;
                    $offset++;
                }
            }
        }

        foreach ($vars as $name => $var)
        {
            if (!isset($result[$name]))
            {
                if (!$var['optional'])
                {
                    throw new CliCommand_Exception("Expected argument \"{$name}\" not found.");
                }
                $result[$name] = $var['multi'] ? array() : null;
            }
        }

        return $result;
    }

    /**
     * 分析选项
     *
     * @param array $input
     *
     * @return array
     */
    protected function _parseOptions(array $input)
    {
        $options = array();
        foreach ($input as $opt)
        {
            $arr = explode(':', $opt);
            $opt = array_shift($arr);
            $val = array_shift($arr);
            if (empty($val)) $val = $opt;

            $options[$opt] = $val;
        }
        return $options;
    }
}

class CliCommand_Exception extends Exception
{
    function __construct($msg, $code = 0)
    {
        parent::__construct($msg, $code);
    }
}


