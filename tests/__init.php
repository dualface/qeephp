<?php

namespace tests;

use qeephp\Autoload;
use qeephp\Config;
use qeephp\tools\ILogger;

if (defined('IN_QEEPHP_TESTS')) return;

define('IN_QEEPHP_TESTS', true);
define('ROOT_PATH', dirname(__DIR__));
define('PACKAGES_PATH', ROOT_PATH . '/packages');
define('QEEPHP_SRC_PATH', PACKAGES_PATH . '/qeephp');
define('TEST_SRC_PATH', __DIR__);

error_reporting(E_ALL | E_STRICT);

require_once QEEPHP_SRC_PATH . '/__init.php';

Autoload::import(PACKAGES_PATH);
Autoload::import(TEST_SRC_PATH, '\\tests');

$logger_config = array(
    'filename' => __DIR__ . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'tests.log',
    'level' => ILogger::TRACE,
);
Config::set('logger.test', $logger_config);

