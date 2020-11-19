<?php

/**
 * 加解密类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use SlimCMS\Abstracts\ModelAbstract;

class Crypt extends ModelAbstract
{
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
        $keys = &self::$setting['keys'];
        $str = openssl_encrypt($str, 'des', $keys['prikey'], 0, $keys['pubkey']);
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
        $keys = &self::$setting['keys'];
        $data = openssl_decrypt($str, 'des', $keys['prikey'], 0, $keys['pubkey']);
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
        return substr(md5($pwd . self::$setting['security']['authkey']), 5, 20);
    }
}
