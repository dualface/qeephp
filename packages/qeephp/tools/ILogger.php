<?php

namespace qeephp\tools;

interface ILogger
{
    const TRACE = 1;
    const DEBUG = 2;
    const INFO  = 3;
    const WARN  = 4;
    const ERROR = 5;
    const FATAL = 6;

    function trace($message);
    function debug($message);
    function info($message);
    function warn($message);
    function error($message);
    function fatal($message);
    function log($level, $message);
    function flush();
}

