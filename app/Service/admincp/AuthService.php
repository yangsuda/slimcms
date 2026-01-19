<?php

/**
 * 认证业务实现
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Service\admincp;

use App\Service\table\AdminloginlogService;
use App\Service\table\AdminService;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Interfaces\OutputInterface;

class AuthService extends ServiceAbstract
{
    /**
     * 登录操作
     * @param string $userid
     * @param string $pwd
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function loginCheck($userid, $pwd): OutputInterface
    {
        if (empty($userid) || empty($pwd)) {
            return self::$output->withCode(21002);
        }
        $row = AdminService::instance()
            ->withWhere(['userid' => $userid, 'status' => 1])
            ->withRespExtraRowFields('groupid')->fetch('id,pwd,groupid,userid,logintime,loginip,realname');
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        if (AdminloginlogService::instance()->withWhere(['userid' => $userid, 'start' => TIMESTAMP - 3600])->count() >= 3) {
            return self::$output->withCode(223014);
        }
        $ip = Ipdata::getip();
        if (!Crypt::pwdVerify($pwd, $row['pwd'])) {
            AdminloginlogService::instance()->add(['userid' => $userid, 'pwd' => $pwd, 'createtime' => TIMESTAMP, 'ip' => $ip]);
            return self::$output->withCode(211032);
        }
        AdminService::instance()->update((int)$row['id'], ['loginip' => $ip, 'logintime' => TIMESTAMP]);
        unset($row['pwd']);
        return self::$output->withCode(200, 24070)->withData($row);
    }

    /**
     * 登录信息
     * @param int $adminid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function loginInfo(int $adminid): OutputInterface
    {
        if (empty($adminid)) {
            return self::$output->withCode(21002)->withReferer('?p=login');
        }
        $cachekey = self::cacheKey(__FUNCTION__, $adminid);
        $row = self::$redis->get($cachekey);
        if (empty($row)) {
            $row = AdminService::instance()
                ->withWhere(['ids' => $adminid, 'status' => 1])
                ->withRespExtraRowFields('groupid')->fetch('id,groupid,userid,logintime,loginip,realname');
            if (empty($row)) {
                return self::$output->withCode(21001)->withReferer('?p=login');
            }
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
    private function allow(array $user, string $n = ''): bool
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
    public function checkAllow(array $user, string $n): OutputInterface
    {
        $isallow = $this->allow($user, $n);
        if (!$isallow) {
            return self::$output->withCode(21048);
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
    public function updatePwd(string $userid, string $pwd, string $newpwd): OutputInterface
    {
        if (empty($newpwd)) {
            return self::$output->withCode(21002);
        }
        if (!preg_match('/^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,32}$/i', $newpwd)) {
            return self::$output->withCode(223032);
        }
        $res = $this->loginCheck($userid, $pwd);
        if ($res->getCode() != 200) {
            return $res;
        }
        AdminService::instance()->update((int)$res->getData()['id'], ['pwd' => Crypt::pwd($newpwd)]);
        return self::$output->withCode(200)->withReferer('?p=login/logout');
    }
}
