<?php

if (defined('QEE_VER')) return;

require_once __DIR__ . '/Autoload.php';

/**
 * DIRECTORY_SEPARATOR 的简写
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * QeePHP 框架基本库所在路径
 */
define('QEE_PATH', rtrim(__DIR__, '/\\') . DS);

/**
 * 确定 QeePHP 是否使用调试模式
 */
defined('QEE_DEBUG') or define('QEE_DEBUG', false);

/**
 * 定义 QeePHP 版本号
 */
define('QEE_VER', 'qeephp-3.0');

/**
 * 返回应用程序对象
 *
 * @return \qeephp\mvc\App
 */
function app()
{
    return \qeephp\mvc\App::instance();
}

/**
 * 对字符串或数组进行格式化，返回格式化后的数组
 *
 * $input 参数如果是字符串，则首先以“,”为分隔符，将字符串转换为一个数组。
 * 接下来对数组中每一个项目使用 trim() 方法去掉首尾的空白字符。最后过滤掉空字符串项目。
 *
 * 该方法的主要用途是将诸如：“item1, item2, item3” 这样的字符串转换为数组。
 *
 * @code php
 * $input = 'item1, item2, item3';
 * $output = Q::arr($input);
 * // $output 现在是一个数组，结果如下：
 * // $output = array(
 * //   'item1',
 * //   'item2',
 * //   'item3',
 * // );
 * @endcode
 *
 * 可以通过 $delimiter 参数指定使用什么字符来分割：
 *
 * @code php
 * $input = 'item1|item2|item3';
 * // 指定使用“|”字符作为分割符
 * $output = Q::arr($input, '|');
 * @endcode
 *
 * @param array|string $input 要格式化的字符串或数组
 * @param string $delimiter 按照什么字符进行分割
 *
 * @return array 格式化结果
 */
function arr($input, $delimiter = ',')
{
    if (!is_array($input))
    {
        $input = explode($delimiter, $input);
    }
    $input = array_map('trim', $input);
    return array_filter($input, 'strlen');
}

/**
 * 构造 URL 地址
 *
 * @param string $action_name 动作名
 * @param array|string $params 要添加到 URL 中的附加参数
 *
 * @return string 构造好的 URL 地址
 */
function url($action_name, $params = null)
{
    return \qeephp\mvc\App::instance()->url($action_name, $params);
}

/**
 * 转换 HTML 特殊字符，等同于 htmlspecialchars()
 *
 * @param string $text
 *
 * @return string
 */
function h($text)
{
    return htmlspecialchars($text);
}

/**
 * 输出转义后的字符串
 *
 * @param string $text
 */
function p($text)
{
    echo htmlspecialchars($text);
}

function val($arr, $name, $default = null)
{
    return isset($arr[$name]) ? $arr[$name] : $default;
}

function request($name, $default = null)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
}

function get($name, $default = null)
{
    return isset($_GET[$name]) ? $_GET[$name] : $default;
}

function post($name, $default = null)
{
    return isset($_POST[$name]) ? $_POST[$name] : $default;
}

function cookie($name, $default = null)
{
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
}

function session($name, $default = null)
{
    return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
}

function server($name, $default = null)
{
    return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
}

function env($name, $default = null)
{
    return isset($_ENV[$name]) ? $_ENV[$name] : $default;
}

/**
 * 取得请求的 URI 信息（不含协议、主机名）
 *
 * 例如：
 *
 * http://qeephp.com/admin/index.php?controller=test
 *
 * 返回：
 *
 * /admin/index.php?controller=test
 *
 * @return string
 */
function get_request_uri()
{
    static $request_uri = null;
    if (!is_null($request_uri)) return $request_uri;

    if (isset($_SERVER['HTTP_X_REWRITE_URL']))
    {
        $request_uri = $_SERVER['HTTP_X_REWRITE_URL'];
    }
    elseif (isset($_SERVER['REQUEST_URI']))
    {
        $request_uri = $_SERVER['REQUEST_URI'];
    }
    elseif (isset($_SERVER['ORIG_PATH_INFO']))
    {
        $request_uri = $_SERVER['ORIG_PATH_INFO'];
        if (!empty($_SERVER['QUERY_STRING']))
        {
            $request_uri .= '?' . $_SERVER['QUERY_STRING'];
        }
    }
    else
    {
        $request_uri = '';
    }

    return $request_uri;
}

/**
 * 取得请求的 URI 信息（不含协议、主机名、查询参数、PATHINFO）
 *
 * 例如：
 *
 * http://qeephp.com/admin/index.php?controller=test
 * http://qeephp.com/admin/index.php/path/to
 *
 * 都返回：
 *
 * /admin/index.php
 *
 * @return string
 */
function get_request_baseuri()
{
    static $request_base_uri = null;
    if (!is_null($request_base_uri)) return $request_base_uri;

    $uri = get_request_uri();
    $pos = strpos($uri, '?');
    {
        $uri = substr($uri, 0, $pos);
    }
    $pathinfo = get_request_pathinfo();
    $len = strlen($pathinfo);
    if ($len)
    {
        $uri = rtrim($uri, '/');
        if (substr($uri, -$len) == $pathinfo)
        {
            $uri = substr($uri, 0, -$len);
        }
    }
    $request_base_uri = $uri;
    return $uri;
}

/**
 * 取得响应请求的 .php 文件在 URL 中的目录部分
 *
 * 例如：
 *
 * http://qeephp.com/admin/index.php?controller=test
 *
 * 返回：
 *
 * /admin/
 *
 * @return string
 */
function get_request_dir()
{
    static $dir = null;
    if (!is_null($dir)) return $dir;

    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    return $dir;
}

/**
 * 返回 PATHINFO 信息
 *
 * 例如：
 *
 * http://qeephp.com/admin/index.php/path/to
 *
 * 返回：
 *
 * /path/to
 *
 * @return string
 */
function get_request_pathinfo()
{
    static $pathinfo = null;
    if (!is_null($pathinfo)) return $pathinfo;

    $pathinfo = get_request_uri();
    $pos = strpos($pathinfo, '?');
    if ($pos !== false)
    {
        $pathinfo = substr($pathinfo, 0, $pos);
    }
    $pathinfo = (string)substr($pathinfo, strlen($_SERVER['SCRIPT_NAME']));
    $pathinfo = rtrim($pathinfo, '/');
    return $pathinfo;
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function is_ajax()
{
    return strtolower(get_http_header('X_REQUESTED_WITH')) == 'xmlhttprequest';
}

function is_flash()
{
    return strtolower(get_http_header('USER_AGENT')) == 'shockwave flash';
}

function get_http_header($header)
{
    $name = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
    return server($name, '');
}

/**
 * QDebug::dump() 的简写，用于输出一个变量的内容
 *
 * @param mixed $vars 要输出的变量
 * @param string $label 输出变量时显示的标签
 * @param int $depth
 * @param bool $return
 *
 * @return string
 */
function dump($vars, $label = null, $depth = null, $return = false)
{
    if ($return) ob_start();
    \qeephp\debug\Debug::dump($vars, $label, $depth);
    if ($return) return ob_get_clean();
}

