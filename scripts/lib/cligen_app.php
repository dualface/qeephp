<?php
/**
 * 定义 CliGenApp 类
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @version $Id: cligen_app.php 2391 2009-04-04 17:56:25Z dualface $
 * @package core
 */

// {{{ includes
require dirname(__FILE__) . '/clicommand_bootstrap.php';
require dirname(__FILE__) . '/cligen_abstract.php';
// }}}

/**
 * CliGenApp 是基于命令行的应用程序生成器
 *
 * @link http://qeephp.com/
 * @copyright Copyright (c) 2006-2009 Qeeyuan Inc. {@link http://www.qeeyuan.com}
 * @license New BSD License {@link http://qeephp.com/license/}
 * @version $Id: cligen_app.php 2391 2009-04-04 17:56:25Z dualface $
 * @package core
 */
class CliGen_App extends CliGen_Abstract
{
    protected $_argv_pattern = array(
        'pwd',
        'appname',
        '?dest_dir|-d',
        '?tpl_name|-t',
        '*options|-o',
    );

    function execute()
    {
        $dest_dir = (!empty($this->_argv['dest_dir']))
            ? $this->_argv['dest_dir'] : $this->_argv['pwd'];

        if (!realpath($dest_dir))
        {
            echo "ERROR: Invalid \"dest_dir\" is \"{$dest_dir}\".\n\n";
            self::help();
            exit(1);
        }

        $options = $this->_parseOptions($this->_argv['options']);

        QGenerator_Application::create()
                ->selectAppTpl($this->_argv['tpl_name'])
                ->selectDestDir($dest_dir)
                ->setOptions($options)
                ->generate($this->_argv['appname']);
    }

    /**
     * 显示帮助信息
     */
    static function help()
    {
        echo <<<EOT

newapp <appname> [...]

syntax:
    script/newapp <appname> [-d dest_dir] [-t tpl_name] [-o option ...]

examples:
    script/newapp myapp
    script/newapp myapp -d ~/public_html
    script/newapp myapp -d d:\\www -o vhost:true



EOT;
    }

}

// 执行命令行脚本
clicommand_bootstrap('CliGen_App');

