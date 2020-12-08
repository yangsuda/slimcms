<?php
/**
 * 微信公众号类
 * @author zhucy
 * @date 2020.01.07
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\File;
use SlimCMS\Helper\Http;
use SlimCMS\Interfaces\OutputInterface;

class Wxgzh extends ModelAbstract
{
    protected static $accessToken = '';

    /**
     * 获取access_token
     * @param OutputInterface $output
     * @return OutputInterface
     */
    private static function getAccessToken(OutputInterface $output): OutputInterface
    {
        $data = $output->getData();
        if (empty($data['appid']) || empty($data['appsecret'])) {
            return self::$output->withCode(21003);
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $data['appid'] . '&secret=' . $data['appsecret'];
        if (self::$redis->isAvailable()) {
            $cachekey = self::cacheKey(__FUNCTION__, $data['appid']);
            self::$accessToken = self::$redis->get($cachekey);
            if (!self::$accessToken) {
                $str = Http::curlGet($url);
                $obj = json_decode($str, true);
                if (!empty($obj['access_token'])) {
                    self::$accessToken = $obj['access_token'];
                    self::$redis->set($cachekey, self::$accessToken, 7000);
                } else {
                    return self::$output->withCode(21000, ['msg' => $obj['errmsg']]);
                }
            }
        } else {
            $dir = CSDATA . 'wx/accessToken/';
            File::mkdir($dir);
            $cacheFile = $dir . 'gzh_' . $data['appid'] . '.txt';
            $filemtime = is_file($cacheFile) ? filemtime($cacheFile) : 0;
            if (TIMESTAMP - $filemtime < 7000) {
                self::$accessToken = file_get_contents($cacheFile);
            } else {
                $str = Http::curlGet($url);
                $obj = json_decode($str, true);
                if (!empty($obj['access_token'])) {
                    file_put_contents($cacheFile, $obj['access_token']);
                    self::$accessToken = $obj['access_token'];
                } else {
                    return self::$output->withCode(21000, ['msg' => $obj['errmsg']]);
                }
            }
        }
        return self::$output->withCode(200)->withData(['accessToken' => self::$accessToken]);
    }


    /**
     * 获取CODE
     * @param OutputInterface $output
     * @return OutputInterface
     */
    public static function getCode(OutputInterface $output): OutputInterface
    {
        $data = $output->getData();
        if (empty($data['appid']) || empty($data['appsecret'])) {
            return self::$output->withCode(21003);
        }
        $scope = aval($data, 'scope') == 'base' ? 'base' : 'userinfo';
        header("Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $data['appid'] .
            "&redirect_uri=" . urlencode(aval($data, 'redirect')) . "&response_type=code&scope=snsapi_" . $scope .
            "&state=#wechat_redirect");
        exit;
    }

    /**
     * 获取信息用户信息
     * @param OutputInterface $output
     * @return OutputInterface
     */
    public static function getUserInfo(OutputInterface $output): OutputInterface
    {
        $data = $output->getData();
        if (empty($data['appid']) || empty($data['appsecret']) || empty($data['code'])) {
            return self::$output->withCode(21003);
        }
        $wxinfo = Http::curlGet("https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $data['appid'] .
            "&secret=" . $data['appsecret'] . "&code=" . $data['code'] . "&grant_type=authorization_code");
        $wxinfo = json_decode($wxinfo, true);
        if (aval($wxinfo, 'scope') == 'snsapi_userinfo' && $wxinfo['access_token'] && $wxinfo['openid']) {
            $wxinfo = json_decode(Http::curlGet("https://api.weixin.qq.com/sns/userinfo?access_token="
                . $wxinfo['access_token'] . "&openid=" . $wxinfo['openid'] . "&lang=zh_CN"), true);
        }
        if (!empty($wxinfo['errcode'])) {
            return self::$output->withCode(21000, ['msg' => $wxinfo['errmsg']]);
        }
        return self::$output->withCode(200)->withData(['wxuser' => $wxinfo]);
    }

    /**
     * 发送模板消息
     * @param OutputInterface $output
     * @return OutputInterface
     */
    public static function sendTemplateMessage(OutputInterface $output): OutputInterface
    {
        $data = $output->getData();
        if (!self::$accessToken) {
            $res = self::getAccessToken();
            if ($res->getCode() != 200) {
                return $res;
            }
        }
        if (empty($data['touser']) || empty($data['template_id']) || empty($data['data']['first'])) {
            return self::$output->withCode(21003);
        }
        $val = [];
        $val['touser'] = $data['touser'];
        $val['template_id'] = $data['template_id'];

        $sendData = [];
        $sendData['first']['value'] = $data['data']['first'];
        $sendData['first']['color'] = aval($data, 'data/firstColor', '#000000');
        if (!empty($data['data']['keyword'])) {
            foreach ($data['data']['keyword'] as $k => $v) {
                $i = $k + 1;
                $sendData['keyword' . $i]['value'] = $data['data']['keyword' . $i];
                $sendData['keyword' . $i]['color'] = aval($data, 'data/color' . $i, '#000000');
            }
        }
        if (!empty($data['data']['remark'])) {
            $sendData['remark']['value'] = $data['data']['remark'];
            $sendData['remark']['color'] = aval($data, 'data/remarkColor', '#000000');
        }
        $val['data'] = $sendData;

        isset($data['url']) && $val['url'] = $data['url'];

        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . self::$accessToken;
        $result = Http::curlPost($url, json_encode($val));
        $obj = json_decode($result, true);
        if (!empty($obj['errcode'])) {
            return self::$output->withCode(21000, ['msg' => $obj['errmsg']]);
        }
        return self::$output->withCode(200);
    }

    /**
     * 生成微信分享签名
     * @param $url
     * @return array
     */
    public static function wxJsapiConfig(OutputInterface $output): OutputInterface
    {
        $data = $output->getData();
        if (empty($data['appid']) || empty($data['appsecret']) || empty($data['url'])) {
            return self::$output->withCode(21003);
        }
        $ticket = self::jsapiTicket();
        if (empty($ticket)) {
            return self::$output->withCode(23003);
        }
        $data = [];
        $data['appid'] = self::$appid;
        $data['ticket'] = $ticket;
        $data['noncestr'] = md5(TIMESTAMP);
        $data['timestamp'] = TIMESTAMP;
        $data['url'] = $data['url'];
        $data['signature'] = sha1('jsapi_ticket=' . $data['ticket'] . '&noncestr=' . $data['noncestr'] .
            '&timestamp=' . $data['timestamp'] . '&url=' . $data['url']);
        return self::$output->withCode(200)->withData($data);
    }

    /**
     * 获取api_ticket
     * @param OutputInterface $output
     * @return OutputInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private static function jsapiTicket(OutputInterface $output): OutputInterface
    {
        $data = $output->getData();
        if (!self::$accessToken) {
            $res = self::getAccessToken();
            if ($res->getCode() != 200) {
                return $res;
            }
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . self::$accessToken . '&type=jsapi';
        if (self::$redis->isAvailable()) {
            $cachekey = self::cacheKey(__FUNCTION__, $data['appid']);
            $ticket = self::$redis->get($cachekey);
            if (empty($ticket)) {
                if (!self::$redis->setnx($cachekey . 'setnx', '1', 600)) {
                    return false;
                }
                $str = Http::curlGet($url);
                $re = json_decode($str, true);
                self::log('获取api_ticket', $re, 'wx/jsapiTicket');
                if (!empty($re['ticket'])) {
                    self::$redis->set($cachekey, $re['ticket'], 7000);
                    return $re['ticket'];
                }
                return false;
            }
            return $ticket;
        } else {
            $dir = CSDATA . 'wx/jsapiTicket/';
            File::mkdir($dir);
            $cacheFile = $dir . $data['appid'] . '.txt';
            $filemtime = is_file($cacheFile) ? filemtime($cacheFile) : 0;
            if (TIMESTAMP - $filemtime < 7000) {
                return file_get_contents($cacheFile);
            } else {
                $str = Http::curlGet($url);
                $re = json_decode($str, true);
                self::log('获取api_ticket', $re, 'wx/jsapiTicket');
                if (!empty($re['ticket'])) {
                    file_put_contents($cacheFile, $re['access_token']);
                    return $re['ticket'];
                }
                return false;
            }
        }
    }
}
