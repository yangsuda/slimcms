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
 * 获取配置信息
 * @return array|mixed
 */
function getConfig()
{
    static $cfg = [];
    if (empty($cfg)) {
        $cfg = require_once CSDATA . 'ConfigCache.php';
        $cfg['settings'] = [];
        if (is_file(CSROOT . 'config/settings.php')) {
            $settings = require_once CSROOT . 'config/settings.php';
            $cfg = array_merge($cfg, $settings);
        }
        $cfg['cfg']['referer'] = aval($_SERVER, 'HTTP_REFERER');
        $cfg['cfg']['clienttype'] = 0;

        //防止最后不加/导致ueditor等加载出错
        $cfg['cfg']['basehost'] = rtrim($cfg['cfg']['basehost'], '/') . '/';

        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (!empty($cfg['referer']) && strpos($cfg['referer'], 'servicewechat.com')) {
            $cfg['cfg']['clienttype'] = 2;//微信小程序
        } elseif (preg_match('/MicroMessenger/i', $agent)) {
            $cfg['cfg']['clienttype'] = 3;//微信WAP
        } elseif (preg_match('/NetFront|iPhone|MIDP-2.0|Opera Mini|UCWEB|Android|Windows CE/i', $agent)) {
            $cfg['cfg']['clienttype'] = 1;//WAP
        }

        if (strpos(aval($_SERVER, 'HTTP_ACCEPT_ENCODING'), 'gzip') === false
            || !function_exists('ob_gzhandler')) {
            $cfg['settings']['output']['gzip'] = false;
        }
    }
    return $cfg;
}

/**
 * 版本比较
 * @param $ver
 * @param string $operator
 * @return bool
 */
function versionCheck($ver, $operator = '<=')
{
    if (strpos($operator, '<') !== false) {
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
function ueditor($identifier, $default = '', $config = ['identity' => 'small'])
{
    return cs090\helper\Ueditor::ueditor($identifier, $default, $config);
}