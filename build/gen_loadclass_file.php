<?php

/**
 * 创建 QeePHP 框架类载入文件
 */

$dir = dirname(dirname(__FILE__));
$source_dir = "{$dir}/framework";
$output_file = "{$dir}/framework/core/configs/qeephp_class_files.php";

require_once dirname(__FILE__) . '/command/loadclass.php';

Command_LoadClass::create()->sourceDir($source_dir)
                           ->outputFile($output_file)
                           ->execute();



