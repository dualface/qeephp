<?php

namespace qeephp\mvc;

/**
 * 视图对象
 */
class View
{
    /**
     * 视图文件所在目录
     *
     * @var string
     */
    public $view_dir;

    /**
     * 视图默认使用的布局
     *
     * @var string
     */
    public $view_layout;

    /**
     * 默认使用的视图
     *
     * @var string
     */
    public $viewname;

    /**
     * 视图变量
     *
     * @var array
     */
    public $vars;

    /**
     * 构造函数
     *
     * @param string $view_dir
     * @param string $viewname
     * @param array $vars
     */
    function __construct($view_dir, $viewname, array $vars)
    {
        $this->view_dir = $view_dir;
        $this->vars = $vars;
        $this->vars['_BASE_DIR'] = get_request_dir();
        $this->viewname = $viewname;
    }

    /**
     * 渲染一个视图文件，返回结果
     *
     * @return string
     */
    function execute()
    {
        $viewname = $this->viewname;
        $child = new ViewLayer($this, $viewname);

        $error_reporting = ini_get('error_reporting');
        error_reporting($error_reporting & ~E_NOTICE);
        $child->parse();

        $layer = $child;
        while (($parent = $layer->parent) != null)
        {
            $parent->parse($layer->blocks);
            $layer = $parent;
        }

        error_reporting($error_reporting);
        return $child->root()->contents;
    }

    /**
     * 查找指定视图文件
     *
     * @param string $viewname
     *
     * @return string
     */
    function view_filename($viewname)
    {
        $filename = str_replace('.', DIRECTORY_SEPARATOR, $viewname) . '.php';
        return $this->view_dir . DIRECTORY_SEPARATOR . $filename;
    }
}

