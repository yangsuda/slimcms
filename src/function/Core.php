<?php
/**
 * 获取数组中某一元素
 * @param $arr 数组
 * @param $val 元素
 * @param $default 都不存在时的默认值
 */
function aval($arr, $val, $default = null)
{
    $arr = empty($arr) ? array() : (array)$arr;
    if (($pos = strpos($val, '/')) !== false) {
        $str1 = substr($val, 0, $pos);
        $str2 = trim(substr($val, $pos), '/');
        if (!isset($arr[$str1])) {
            return $default;
        }
        return aval($arr[$str1], $str2, $default);
    }
    return isset($arr[$val]) ? $arr[$val] : $default;
}

/**
 * 伪静态URL处理
 */
function rewriteUrl($url, $scriptname = '')
{
    $cfg = \cs090\core\Config::$config;
    if (preg_match('/^&/', $url)) {
        $url = \cs090\helper\Http::currentUrl() . $url;
    }
    $scriptname = $scriptname ?: CURSCRIPT;
    if (empty($cfg['cfg']['rewritestatus'])) {
        return $url;
    }

    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $output);
    $basehost = aval($cfg, 'cfg/' . $scriptname . 'host', aval($cfg, 'cfg/basehost'));
    $basepath = preg_match('/\/' . $scriptname . '\//i', $url) ? '' : $basehost;

    $url = rtrim($basepath, '/') . '/' . rtrim($output['p'], '/') . '/';
    $jsoncallback = !empty($output['jsoncallback']);
    unset($output['p'], $output['q'], $output['jsoncallback']);
    if (!empty($output)) {
        $arr = array();
        foreach ($output as $k => $v) {
            $v = is_array($v) ? implode('`', $v) : $v;
            if (!empty($v) || $v == '0') {
                $arr[] = urlencode(str_replace(array('-', '_'), array('&#045;', '&#095;'), $k) . '-' . str_replace(array('-', '_'), array('&#045;', '&#095;'), $v));
            }
        }
        if ($arr) {
            $val = implode('_', $arr);
            $url .= urlencode($val) . '.html';
            //方便JS中url的拼接生成URL
            $url = str_replace(array('%2527%2B', '%2B%2527'), array('\'+', '+\''), $url);
        }
    }
    return $url . ($jsoncallback ? '?jsoncallback=?' : '');
}

/**
 * 版本比较
 * @param $ver
 * @param string $operator
 * @return bool
 */
function versionCheck($ver,$operator='<=')
{
    if(strpos($operator,'<')!==false){
        return !defined('VERSION') || defined('VERSION') && version_compare(VERSION, $ver, $operator);
    }
    return defined('VERSION') && version_compare(VERSION, $ver, $operator);
}

/**
 * 生成小图
 * @param $pic
 * @param int $width
 * @param int $height
 * @return mixed|string
 */
function copyImage($pic, $width = 1000, $height = 1000)
{
    return SlimCMS\Core\Image::copyImage($pic, $width, $height);
}

/**
 * 富文本编辑器
 * @param $identifier
 * @param string $default
 * @param array $config
 * @return string
 */
function ueditor($identifier, $default='', $config = ['identity' => 'small'])
{
    return cs090\helper\Ueditor::ueditor($identifier, $default, $config);
}