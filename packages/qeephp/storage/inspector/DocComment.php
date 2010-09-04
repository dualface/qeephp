<?php

namespace qeephp\storage\inspector;

class DocComment
{
    /**
     * 分析 DocComment，返回分析结果
     *
     * @param string $doc
     *
     * @return array
     */
    static function parse($doc)
    {
        $doc = trim(trim($doc), '/');
        $doc = trim(preg_replace('/^\s*\**( |\t)?/m', '', $doc));
        $matches = null;
		if(preg_match('/^\s*@\w+/m', $doc, $matches, PREG_OFFSET_CAPTURE))
		{
			$items = substr($doc, $matches[0][1]);
			$text  = trim(substr($doc, 0, $matches[0][1]));
		}
        else
        {
            $items = '';
            $text  = $doc;
        }

        return self::_format_items($items);
    }

    private static function _format_items($items)
    {
        $items = preg_split('/^\s*(@|#)/m', $items, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $arr = array();
        for ($offset = 0; $offset < count($items); $offset++)
        {
            $item = $items[$offset];
            if ($item == '@') continue;
            if ($item == '#')
            {
                $offset++;
                continue;
            }
			$arr[] = self::_format_item($item);
        }

        $ref_counts = array();
        $items = array();
        foreach ($arr as $item)
        {
            foreach ($item as $name => $value)
            {
                if (isset($items[$name]))
                {
                    if (!isset($ref_counts[$name]))
                    {
                        $ref_counts[$name] = true;
                        $items[$name] = array($items[$name], $value);
                    }
                    else
                    {
                        $items[$name][] = $value;
                    }
                }
                else
                {
                    $items[$name] = $value;
                }
            }
        }

        return $items;
    }

    private static function _format_item($item)
    {
        $segs = preg_split('/\s+/', trim($item), 2);
        $name = $segs[0];
        $params = isset($segs[1]) ? trim($segs[1]) : '';
        return array($name => self::_format_params($params));
    }

    private static function _format_params($params)
    {
        $params = arr($params, "\n");
        $return = array();
        $useKey = false;
        foreach ($params as $offset => $param)
        {
            $param = self::_format_param($param);
            if (is_array($param))
            {
                $useKey = true;
                $return[$param[0]] = $param[1];
            }
            else
            {
                $return[] = $param;
            }
        }
        if (!$useKey && count($return) == 1) $return = reset($return);
        if (empty($return)) return '';
        return $return;
    }

    private static function _format_param($param)
    {
        $pair = arr($param, ':');
        if (isset($pair[1]))
        {
            return $pair;
        }
        else
        {
            return $pair[0];
        }
    }
}

