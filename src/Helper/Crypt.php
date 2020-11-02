<?php

/**
 * 加解密类
 * @author zhucy
 * @date 2019.09.18
 */

namespace SlimCMS\Helper;

use cs090\core\Config;

class Crypt
{
    private static function getConfig()
    {
        $cfg = require_once CSROOT . 'config/settings.php';
        return $cfg['settings'];
    }

    /**
     * 加密
     * @param $str
     * @return string
     */
    public static function encrypt($str, $type = 'noplus')
    {
        if (empty($str)) {
            return '';
        }
        if (is_array($str)) {
            $str = serialize($str);
        }
        $cfg = self::getConfig();
        $str = openssl_encrypt($str, 'des', $cfg['keys']['prikey'], 0, $cfg['keys']['pubkey']);
        if ($type == 'noplus') {
            $str = str_replace('+', '.', $str);
        }
        return $str;
    }

    /**
     * 解密
     * @param $str
     * @return mixed|string
     */
    public static function decrypt($str, $type = 'noplus')
    {
        if (empty($str)) {
            return '';
        }
        if ($type == 'noplus') {
            $str = str_replace('.', '+', $str);
        }
        $cfg = self::getConfig();
        $data = openssl_decrypt(urldecode(str_replace('%25', '%', urlencode($str))), 'des', $cfg['keys']['prikey'], 0, $cfg['keys']['pubkey']);
        $result = '';
        if (!empty($data) && preg_match("/^[a]:[0-9]+:{(.*)}$/", $data)) {
            $result = unserialize($data);
        }
        if (!is_array($result)) {
            $result = $data;
        }
        return $result;
    }

    /**
     * 生成系统密码
     * @param $pwd
     * @return bool|string
     */
    public static function pwd($pwd)
    {
        $cfg = self::getConfig();
        return substr(md5($pwd . $cfg['security']['authkey']), 5, 20);
    }
}
