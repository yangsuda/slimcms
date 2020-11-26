<?php

/**
 * 加解密类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Helper;

use SlimCMS\Error\TextException;

class Crypt
{

    private static function settings()
    {
        static $settings = [];
        if(empty($settings)){
            if(!is_file(CSROOT . 'config/settings.php')){
                throw new TextException(21060, '', 'settings');
            }
            $settings = require CSROOT . 'config/settings.php';
        }
        return $settings['settings'];
    }
    /**
     * 加密
     * @param $str
     * @return string
     */
    public static function encrypt($str): string
    {
        if (empty($str)) {
            return '';
        }
        if (is_array($str)) {
            $str = serialize($str);
        }
        $settings = self::settings();
        $str = openssl_encrypt($str, 'des', $settings['keys']['prikey'], 0, $settings['keys']['pubkey']);
        $str = str_replace('+', '.', $str);
        return $str;
    }

    /**
     * 解密
     * @param $str
     * @return mixed|string
     */
    public static function decrypt(string $str): string
    {
        if (empty($str)) {
            return '';
        }
        $str = str_replace('.', '+', $str);
        $str = urldecode(str_replace('%25', '%', urlencode($str)));
        $settings = self::settings();
        $data = openssl_decrypt($str, 'des', $settings['keys']['prikey'], 0, $settings['keys']['pubkey']);
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
    public static function pwd($pwd): string
    {
        $settings = self::settings();
        return substr(md5($pwd . $settings['security']['authkey']), 5, 20);
    }
}
