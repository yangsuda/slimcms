<?php

/**
 * 登录模型类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\admincp;

use App\Core\Forms;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Interfaces\OutputInterface;

class LoginModel extends ModelAbstract
{
    /**
     * 登录操作
     * @param string $userid
     * @param string $pwd
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function loginCheck($userid, $pwd): OutputInterface
    {
        if (empty($userid) || empty($pwd)) {
            return self::$output->withCode(21002);
        }
        $pwd1 = $pwd;
        $pwd = Crypt::pwd($pwd);
        $row = self::t('admin')->withWhere(['userid' => $userid])->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        if ($row['status'] == 2) {
            return self::$output->withCode(21061);
        }
        $where = ['userid' => $userid, self::t()->field('createtime', (TIMESTAMP - 3600), '>')];
        if (self::t('adminloginlog')->withWhere($where)->count() >= 3) {
            return self::$output->withCode(223014);
        }
        $ip = Ipdata::getip();
        if ($pwd != $row['pwd']) {
            $data = ['userid' => $userid, 'pwd' => $pwd1, 'createtime' => TIMESTAMP, 'ip' => $ip];
            self::t('adminloginlog')->insert($data);
            return self::$output->withCode(211032);
        }
        $row['_groupid'] = self::t('admingroup')->withWhere($row['groupid'])->fetch();
        $row['purviews'] = $row['_groupid']['purviews'];
        self::t('admin')->withWhere($row['id'])->update(['loginip' => $ip, 'logintime' => TIMESTAMP]);
        return self::$output->withCode(200, 24070)->withData($row);
    }

    /**
     * 登录信息
     * @param int $adminid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function loginInfo(int $adminid): OutputInterface
    {
        if (empty($adminid)) {
            return self::$output->withCode(21002)->withReferer('?p=login');
        }
        $cachekey = self::cacheKey(__FUNCTION__, $adminid);
        $row = self::$redis->get($cachekey);
        if (empty($row)) {
            $row = self::t('admin')->withWhere($adminid)->fetch();
            if (empty($row)) {
                return self::$output->withCode(21001)->withReferer('?p=login');
            }
            if ($row['status'] == 2) {
                return self::$output->withCode(21061);
            }
            $row['_groupid'] = self::t('admingroup')->withWhere($row['groupid'])->fetch();
            self::$redis->set($cachekey, $row, 60);
        }
        return self::$output->withCode(200)->withData(['admin' => $row]);
    }

    /**
     * 检验用户是否有权使用某功能
     * @param array $user
     * @param string $n
     * @return bool
     */
    private static function allow(array $user, string $n = ''): bool
    {
        $purviews = empty($user['_groupid']['purviews']) ? '' : $user['_groupid']['purviews'];
        if (empty($n) || preg_match('/admin_AllowAll/i', $purviews)) {
            return true;
        }
        $allows = explode(',', $purviews);
        $ns = explode(',', $n);
        foreach ($ns as $n) {
            //只要找到一个匹配的权限，即可认为用户有权访问此页面
            if (empty($n)) {
                continue;
            }
            if (in_array($n, $allows)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 权限检测
     * @param array $user
     * @param string $n
     * @return OutputInterface
     */
    public static function checkAllow(array $user, string $n): OutputInterface
    {
        $isallow = self::allow($user, $n);
        if (!$isallow) {
            return self::$output->withCode(21048);
        }
        return self::$output->withCode(200);
    }

    /**
     * 后台操作日志
     * @param array $user
     * @return OutputInterface
     */
    public static function logSave(array $user): OutputInterface
    {
        if (!empty(self::$config['adminLog'])) {
            $query = self::$request->getRequest()->getUri()->getQuery();
            $server = self::$request->getRequest()->getServerParams();
            $method = aval($server, 'REQUEST_METHOD');
            $query = $query ?: file_get_contents('php://input');
            $query = substr($query, 0, 200);
            $postinfo = $_POST ? serialize($_POST) : '';
            $postinfo = substr($postinfo, 0, 5000);
            $data = [
                'adminid' => aval($user, 'id'),
                'adminname' => aval($user, 'userid'),
                'method' => $method,
                'query' => $query,
                'ip' => Ipdata::getip(),
                'createtime' => TIMESTAMP,
                'postinfo' => $postinfo,
                'route' => self::input('p')
            ];
            self::t('adminlog')->insert($data);
        }
        return self::$output->withCode(200);
    }

    /**
     * 修改密码
     * @param string $userid 帐号
     * @param string $pwd 原密码
     * @param string $newpwd 新密码
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function updatePwd(string $userid, string $pwd, string $newpwd): OutputInterface
    {
        $res = self::loginCheck($userid, $pwd);
        if ($res->getCode() != 200) {
            return $res;
        }
        if (empty($newpwd)) {
            return self::$output->withCode(21002);
        }
        if (!preg_match('/^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,32}$/i', $newpwd)) {
            return self::$output->withCode(223032);
        }
        $id = $res->getData()['id'];
        $newpwd = Crypt::pwd($newpwd);
        self::t('admin')->withWhere($id)->update(['pwd' => $newpwd]);
        return self::$output->withCode(200)->withReferer('?p=login/logout');
    }
}
