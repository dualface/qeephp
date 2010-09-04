<?php

namespace qeephp\mvc;

/**
 * 视图层
 */
class ViewLayer
{
    /**
     * 该层所属的视图对象
     *
     * @var View
     */
    public $view;

    /**
     * 父层对象
     *
     * @var viewlayer
     */
    public $parent;

    /**
     * 视图名称
     *
     * @var string
     */
    public $viewname;

    /**
     * 该层的内容
     *
     * @var string
     */
    public $contents;

    /**
     * 该层区块的内容
     *
     * @var array
     */
    public $blocks = array();

    /**
     * 该层的区块
     *
     * @var array
     */
    private $_block_stack = array();

    /**
     * 预定义的区块
     *
     * @var array
     */
    private $_predefined_blocks = array();

    /**
     * 构造函数
     *
     * @param View $view
     * @param string $viewname
     */
    function __construct(View $view, $viewname)
    {
        $this->view     = $view;
        $this->viewname = $viewname;
    }

    /**
     * 返回该层的顶级层（最底层的视图）
     *
     * @return viewlayer
     */
    function root()
    {
        return ($this->parent) ? $this->parent->root() : $this;
    }

    /**
     * 分析视图，并返回结果
     *
     * @param array $predefined_blocks
     */
    function parse(array $predefined_blocks = array())
    {
        $this->_predefined_blocks = $predefined_blocks;

        ob_start();
        extract($this->view->vars);
        include $this->view->view_filename($this->viewname);
        $this->contents = ob_get_clean();

        $this->_predefined_blocks = null;
        foreach ($this->blocks as $block_name => $contents)
        {
            $search = "%_view_block.{$block_name}_%";
            if (strpos($this->contents, $search) !== false)
            {
                $this->contents = str_replace($search, $contents, $this->contents);
            }
        }
    }

    /**
     * 从指定层继承
     *
     * @param string $viewname
     */
    function extend($viewname)
    {
        $this->parent = new ViewLayer($this->view, $viewname);
    }

    /**
     * 定义一个区块
     *
     * @param string $block_name
     * @param boolean $append
     */
    function block($block_name, $append = false)
    {
        array_push($this->_block_stack, array($block_name, $append));
        ob_start();
    }

    /**
     * 结束最后定义的一个区块
     */
    function endblock()
    {
        list($block_name, $append) = array_pop($this->_block_stack);
        $contents = ob_get_clean();
        $this->_create_block($contents, $block_name, $append);
    }

    /**
     * 定义一个空区块
     *
     * @param string $block_name
     * @param boolean $append
     */
    function empty_block($block_name, $append = false)
    {
        $this->_create_block('', $block_name, $append);
    }

    /**
     * 载入一个视图片段
     *
     * @param string $viewname 视图片段名
     */
    function element($viewname)
    {
        $__filename = $this->view->view_filename("_elements/{$viewname}");
        extract($this->view->vars);
        include $__filename;
    }

    /**
     * 完成一个区块
     *
     * @param string $contents
     * @param string $block_name
     * @param boolean $append
     */
    private function _create_block($contents, $block_name, $append)
    {
        if (isset($this->_predefined_blocks[$block_name]))
        {
            if ($append)
            {
                $contents .= $this->_predefined_blocks[$block_name];
            }
            else
            {
                $contents = $this->_predefined_blocks[$block_name];
            }
        }

        $this->blocks[$block_name] = $contents;
        echo "%_view_block.{$block_name}_%";
    }
}

