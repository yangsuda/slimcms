<?php

/**
 * 应用模型类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\main;

use App\Core\Forms;
use App\Model\admincp\ApimanageModel;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Interfaces\OutputInterface;

class AppsModel extends ModelAbstract
{
    /**
     * 接口列表
     * @param $id
     * @return array
     */
    public static function apiList(string $appid = null): OutputInterface
    {
        //没开放任何接口，直接返回空
        $appapi = '';
        if ($appid) {
            $appapi = self::t('apps')->withWhere(['appid' => $appid])->fetch('appapi');
            if (empty($appapi)) {
                return self::$output->withCode(200);
            }
        }
        $list = self::t('apigroup')->fetchList();
        foreach ($list as $key => $val) {
            $where = ['groupid' => $val['id']];
            $appapi && $where['id'] = explode(',', $appapi);
            $apiList = self::t('apilist')->withWhere($where)->fetchList();
            if (empty($apiList)) {
                unset($list[$key]);
                continue;
            }

            foreach ($apiList as &$v) {
                $v['apiname'] = str_replace($val['groupname'], '', $v['apiname']);
            }
            $val['list'] = $apiList;
            $list[$key] = $val;
        }
        return self::$output->withCode(200)->withData(['list' => $list]);
    }

    /**
     * 接口详细
     * @param int $identifier
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function apiView(string $identifier): OutputInterface
    {
        if (empty($identifier)) {
            return self::$output->withCode(21002);
        }
        $apiid = (int)self::t('apilist')->withWhere(['identifier' => $identifier])->fetch('id');
        $fid = 16;
        $res = Forms::dataView($fid, $apiid);
        if ($res->getCode() != 200) {
            return $res;
        }
        $data = $res->getData()['row'];

        //请求参数
        $param = [
            'fid' => 18,
            'page' => 1,
            'pagesize' => 1000,
            'where' => ['apiid' => $apiid],
            'by' => 'asc',
            'noinput' => true,
        ];
        $data['requestParam'] = Forms::dataList($param)->getData();

        //响应参数
        $param = [
            'fid' => 19,
            'page' => 1,
            'pagesize' => 1000,
            'where' => ['apiid' => $apiid],
            'by' => 'asc',
            'noinput' => true,
        ];
        $data['responseParam'] = Forms::dataList($param)->getData();
        $fileName = substr(md5(self::$setting['security']['authkey']), -8);
        return self::$output->withCode(200)->withData(['row' => $data, 'fileName' => $fileName]);
    }


    /**
     * 搜索接口列表
     * @param string $words
     * @return OutputInterface
     */
    public static function apiSearch(string $words): OutputInterface
    {
        if(empty($words)){
            return self::$output->withCode(21002);
        }
        $where = [];
        $where[] = self::t('apilist')->field('concat(apiname,path)', $words, 'like');
        $apiList = self::t('apilist')->withWhere($where)->fetchList();
        return self::$output->withCode(200)->withData(['apiList' => $apiList]);
    }

    /**
     * 后台操作日志
     * @param array $user
     * @return OutputInterface
     */
    private static function logSave(string $appid): OutputInterface
    {
        $query = self::$request->getRequest()->getUri()->getQuery();
        $server = self::$request->getRequest()->getServerParams();
        $method = aval($server, 'REQUEST_METHOD');
        $query = $query ?: file_get_contents('php://input');
        $query = substr($query, 0, 200);
        $postinfo = $_POST ? serialize($_POST) : '';
        $postinfo = substr($postinfo, 0, 5000);
        $data = [
            'appid' => $appid,
            'method' => $method,
            'query' => $query,
            'ip' => Ipdata::getip(),
            'createtime' => TIMESTAMP,
            'postinfo' => $postinfo,
            'route' => self::input('p')
        ];
        self::t('applog')->insert($data);
        return self::$output->withCode(200);
    }

    /**
     * 获取accessToken
     * @param string $appid 应用ID
     * @param string $appsecret 应用密钥
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function getAccessToken(string $appid, string $appsecret): OutputInterface
    {
        if (empty($appid) || empty($appsecret)) {
            return self::$output->withCode(21002);
        }
        $row = self::t('apps')->withWhere(['appid' => $appid])->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        if ($row['ischeck'] == 2) {
            return self::$output->withCode(21061);
        }
        if ($row['appsecret'] != $appsecret) {
            return self::$output->withCode(211032);
        }


        $accessToken = Crypt::encrypt(['appid' => $appid, 'time' => TIMESTAMP]);

        $data = [
            'lastip' => Ipdata::getip(),
            'lasttime' => TIMESTAMP,
            'accesstoken' => $accessToken
        ];
        self::t('apps')->withWhere($row['id'])->update($data);
        return self::$output->withCode(200)->withData(['accessToken' => $accessToken]);
    }

    /**
     * AccessToken检测
     * @param string accessToken TOKEN
     * @param string identifierArr 接口唯一标识符
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function checkAccessToken(string $accessToken, $identifierArr): OutputInterface
    {
        if (empty($accessToken) || empty($identifierArr)) {
            return self::$output->withCode(21002);
        }

        //接口权限检测
        $identifier = ApimanageModel::getIdentifier($identifierArr)->getData()['identifier'];
        $api = self::t('apilist')->withWhere(['identifier' => $identifier])->fetch();
        if (empty($api)) {
            return self::$output->withCode(21001);
        }
        if ($api['ischeck'] != 1) {
            return self::$output->withCode(211033);
        }
        //accessToken检验关闭的，直接通过
        if ($api['tokencheck'] != 1) {
            return self::$output->withCode(200);
        }

        //accessToken有效性检测
        $data = Crypt::decrypt($accessToken);
        if (empty($data['time'])) {
            return self::$output->withCode(223018);
        }
        $tokenTTL = !empty(self::$config['tokenTTL']) ? self::$config['tokenTTL'] : 7200;
        if ($data['time'] + $tokenTTL < TIMESTAMP) {
            return self::$output->withCode(223019);
        }

        $appid = (string)$data['appid'];
        $row = self::t('apps')->withWhere(['appid' => $appid])->fetch('ischeck,appapi,accesstoken');
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        if ($row['ischeck'] == 2) {
            return self::$output->withCode(21061);
        }
        if ($accessToken != $row['accesstoken']) {
            return self::$output->withCode(223021);
        }

        if (!in_array($api['id'], explode(',', $row['appapi']))) {
            return self::$output->withCode(211034);
        }

        self::logSave($appid);
        return self::$output->withCode(200);
    }

    /**
     * 接口文档查看登陆
     * @param string $userid
     * @param string $pwd
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function docLogin(string $appid): OutputInterface
    {
        if (empty($appid)) {
            return self::$output->withCode(21002);
        }
        $row = self::t('apps')->withWhere(['appid' => $appid])->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        return self::$output->withCode(200);
    }
}
