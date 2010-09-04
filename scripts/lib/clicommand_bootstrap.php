<?php

global $argv;

if (!isset($argv))
{
    echo <<<EOT
ERR: PHP running under command line without \$argv.

EOT;

    exit(1);
}

require dirname(__FILE__) . '/clicommand_abstract.php';
require dirname(dirname(dirname(__FILE__))) . '/qeephp/q.php';

function clicommand_bootstrap($class)
{
    global $argv;

    try
    {
        $gen = new $class($argv);
        $gen->execute();
    }
    catch (Exception $ex)
    {
        echo "\n";
        echo 'ERROR: ' . $ex->getMessage();
        echo "\n\n";
        call_user_func(array($class, 'help'));
    }
}

