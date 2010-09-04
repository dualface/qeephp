<?php

namespace qeephp\debug;

/**
 * 用于帮助开发者对 QeePHP 调试的工具类
 */
abstract class Debug
{
	/**
	 * 指示输出时，是否转为 HTML 标签
	 *
	 * @var bool
	 */
	private static $_html_output = 'auto';

	/**
	 * 指定输出格式
	 *
	 * @param bool|string $html
	 */
	static function html_output($html = 'auto')
	{
		self::$_html_output = $html;
	}

    /**
     * 输出变量的内容
     *
     * 可以使用 dump() 这个简写形式。
     *
     * @code php
     * dump($vars, '$vars current values');
     * @endcode
     *
     * @param mixed $vars 要输出的变量
     * @param string $label 标签
     * @param int $depth
     */
    static function dump($vars, $label = null, $depth = null)
    {
        $trace = debug_backtrace();
        if ($trace[0]['function'] == 'dump' && $trace[0]['class'] == 'qeephp\\debug\\Debug')
        {
            array_shift($trace);
        }
        $last = array_shift($trace);
        if (val($last, 'class') == 'qeephp\\debug\\Debug' && val($last, 'function') == 'dump')
        {
            $last = array_shift($trace);
        }

        $file = htmlspecialchars($last['file']);
        $line = $last['line'];

        $html_output = self::$_html_output;
        if ($html_output === 'auto')
        {
            $html_output = ini_get('htmlOutput');
        }
        $dump = new DebugDump($html_output, $depth);

        if ($html_output)
        {
            $id = 'dump_block_' . md5("{$file}/{$line}");
            $content = <<<EOT
<div style="font-size: 12px; color: #333; background-color: #fff;
            padding: 10px; font-family: 'Courier New', Courier, monospace;">
    dump from:
    <a href="#" onclick="var e = document.getElementById('{$id}'); if (e.style.display == 'none') { e.style.display = 'block'; } else { e.style.display = 'none'; }; return false;" style="color: green;">{$file}</a>
    <span style="color: red;">({$line})</span>

    <div id="{$id}">
        <pre style="margin: 8px; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9;">

EOT;

            if ($label !== null && $label !== '')
            {
                $label = htmlspecialchars($label);
                $content .= <<<EOT
<span style="font-size: 18px; font-weight: bold; ">***&nbsp;{$label}&nbsp;***</span>

EOT;
            }
            $content .= $dump->escape($vars);

            $content .= <<<EOT

        </pre>
    </div>
</div>

EOT;
        }
        else
        {
            $content = "\ndump form: {$file} ({$line})\n";
            if ($label !== null && $label !== '')
            {
                $content .= $label . " :\n";
            }
            $content .= $dump->escape($vars) . "\n";
        }

        echo $content;
    }

    /**
     * 显示应用程序执行路径
     */
    static function dump_trace()
    {
        $debug = debug_backtrace();
        $lines = '';
        $index = 0;
        $t = realpath(QEE_PATH);
        $l = strlen($t);
        for ($i = count($debug) - 1; $i >= 0; $i--)
        {
            $file = $debug[$i];
            $strong = false;
            if (!isset($file['file']))
            {
                $file['file'] = 'eval';
            }
            else
            {
                if (substr(realpath($file['file']), 0, $l) != $t)
                {
                    $strong = true;
                }
            }
            if (!isset($file['line']))
            {
                $file['line'] = null;
            }

            if ($strong)
            {
                $line = "#{$index} **{$file['file']}({$file['line']})**: ";
            }
            else
            {
                $line = "#{$index} {$file['file']}({$file['line']}): ";
            }
            if (isset($file['class']))
            {
                $line .= "{$file['class']}{$file['type']}";
            }
            $line .= "{$file['function']}(";
            if (isset($file['args']) && count($file['args']))
            {
                foreach ($file['args'] as $arg)
                {
                    $line .= gettype($arg) . ', ';
                }
                $line = substr($line, 0, - 2);
            }
            $line .= ')';
            $lines .= $line . "\n";
            $index ++;
        } // for


        if (ini_get('html_errors'))
        {
            $lines = nl2br(str_replace(' ', '&nbsp;', $lines));
            $lines = preg_replace('/\*\*(.+)\*\*/', '<strong>$1</strong>', $lines);
            echo $lines;
        }
        else
        {
            echo $lines;
        }
    }

    /**
     * 输出异常的详细信息和调用堆栈
     *
     * @code php
     * QException::dump($ex);
     * @endcode
     */
    static function dump_exception(\Exception $ex)
    {
        $out = "Exception '" . get_class($ex) . "' ";
        if ($ex->getMessage() != '')
        {
            $out .= " with message '" . $ex->getMessage() . "'";
        }
        $out .= ' (error code:' . $ex->getCode() . ')';
        $out .= ' in ' . $ex->getFile() . ':' . $ex->getLine() . "\n\n";
        $out .= $ex->getTraceAsString();

        if (self::$_html_output)
        {
            echo nl2br(htmlspecialchars($out));
        }
        else
        {
            echo $out;
        }
    }
}

