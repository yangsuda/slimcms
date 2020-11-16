<?php
/**
 * HTTP请求类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Helper;

use cs090\core\Config;
use cs090\core\Error;
use Exception;

class Http
{
    public static function curlGet(string $url, array $setopt = [])
    {
        $ch = curl_init(); //初始化curl模块
        curl_setopt($ch, CURLOPT_URL, $url); //登录页地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, aval($setopt, 'CURLOPT_SSL_VERIFYPEER', 0)); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, aval($setopt, 'CURLOPT_SSL_VERIFYHOST', 0)); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, aval($setopt, 'CURLOPT_FOLLOWLOCATION', 1)); // 使用自动跳转
        //curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);//把返回$cookie_jar来的cookie信息保存在$cookie_jar文件中
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, aval($setopt, 'CURLOPT_RETURNTRANSFER', 1)); //设定返回的数据是否自动显示
        curl_setopt($ch, CURLOPT_HEADER, aval($setopt, 'CURLOPT_HEADER', false)); //设定是否显示头信息
        curl_setopt($ch, CURLOPT_NOBODY, aval($setopt, 'CURLOPT_NOBODY', false)); //设定是否输出页面内容
        $temp = curl_exec($ch);
        curl_close($ch); //get data after login
        return $temp;
    }

    public static function curlPost($url, $post, $headers = array(), $setopt = [])
    {
        $curl = curl_init(); //启动一个curl会话
        curl_setopt($curl, CURLOPT_URL, $url); //要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, aval($setopt, 'CURLOPT_SSL_VERIFYPEER', 0)); //对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, aval($setopt, 'CURLOPT_SSL_VERIFYHOST', 0)); //从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_SSLVERSION, aval($setopt, 'CURLOPT_SSLVERSION', 1));
        curl_setopt($curl, CURLOPT_HEADER, aval($setopt, 'CURLOPT_HEADER', false)); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_POST, aval($setopt, 'CURLOPT_POST', 1)); //发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); //Post提交的数据包
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, aval($setopt, 'CURLOPT_RETURNTRANSFER', 1)); // 获取的信息以文件流的形式返回
        $headers && curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($curl); //执行一个curl会话
        curl_close($curl); //关闭curl
        return $result;
    }
}
