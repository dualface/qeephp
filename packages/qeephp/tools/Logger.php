<?php

namespace qeephp\tools;

use qeephp\Config;

class Logger implements ILogger
{
    private $_date_format = 'Y-m-d H:i:s';
    private $_log = array();
    private $_cached_size;
    private $_cache_chunk_size;
    private $_filename;
    private $_level;

    private static $_level_names = array(
        1 => 'TRACE',
        2 => 'DBEUG',
        3 => 'INFO',
        4 => 'WARN',
        5 => 'ERROR',
        6 => 'FATAL',
    );

    private function __construct($name)
    {
        $config = Config::get("logger.{$name}");
        $this->_filename = $config['filename'];
        $this->_cache_chunk_size = intval(val($config, 'cache_chunk_size', 65536));
        $this->_date_format = val($config, 'date_format', 'Y-m-d H:i:s');
        $this->_cached_size = 0;
        $this->_level = intval(val($config, 'level', self::WARN));
        $this->trace('--- Logger startup ---');
    }

    function __destruct()
    {
        $this->trace('--- Logger shutdown ---');
        $this->flush();
    }

    /**
     * 返回指定的日志服务对象实例
     *
     * @param string $name
     *
     * @return ILogger
     */
    static function instance($name)
    {
        static $instances = array();
        if (!isset($instances[$name]))
        {
            $instances[$name] = new Logger($name);
        }
        return $instances[$name];
    }

    function trace($message)
    {
        $this->log(self::TRACE, $message);
    }

    function debug($message)
    {
        $this->log(self::DEBUG, $message);
    }

    function info($message)
    {
        $this->log(self::INFO, $message);
    }

    function warn($message)
    {
        $this->log(self::WARN, $message);
    }

    function error($message)
    {
        $this->log(self::ERROR, $message);
    }

    function fatal($message)
    {
        $this->log(self::FATAL, $message);
    }

    function log($level, $message)
    {
        if ($level < $this->_level) return;
        $this->_log[] = array($level, time(), $message);
        $this->_cached_size += strlen($message);
        if ($this->_cached_size >= $this->_cache_chunk_size)
        {
            $this->flush();
        }
    }

    function flush()
    {
        if (empty($this->_log)) return;
        $string = '';
        foreach ($this->_log as $offset => $item)
        {
            unset($this->_log[$offset]);
            list($level, $time, $message) = $item;
            $level = self::$_level_names[$level];
            $string .= "[$level] ";
            $string .= date($this->_date_format, $time);
            $string .= ": {$message}\n";
        }

        $fp = @fopen($this->_filename, 'a');
        if ($fp && @flock($fp, LOCK_EX))
        {
            @fwrite($fp, $string);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        $this->_log = array();
        $this->_cached_size = 0;
    }
}

