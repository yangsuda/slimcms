<?php

/**
 * 登陆模型类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\admincp;

use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Interfaces\OutputInterface;

class LoginModel extends ModelAbstract
{
    /**
     * 登陆操作
     * @param string $userid
     * @param string $pwd
     * @param string $referer
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function loginCheck($userid, $pwd, $referer = ''): OutputInterface
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
        $referer = $referer ?: self::url('?p=main/index');
        return self::$output->withCode(200, 24070)->withData($row)->withReferer($referer);
    }

    /**
     * 登陆信息
     * @param int $adminid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function loginInfo(int $adminid): OutputInterface
    {
        if (empty($adminid)) {
            return self::$output->withCode(21002)->withReferer(self::url('?p=login'));
        }
        $cachekey = self::cacheKey(__FUNCTION__, $adminid);
        $row = self::$redis->get($cachekey);
        if (empty($row)) {
            $row = self::t('admin')->withWhere($adminid)->fetch();
            if (empty($row)) {
                return self::$output->withCode(21001)->withReferer(self::url('?p=login'));
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
     * 修改密码
     * @param $adminid
     * @param $old
     * @param $new
     * @return array
     */
    public static function updatePwd(int $adminid, $old, $new): OutputInterface
    {
        if (empty($adminid) || empty($old) || empty($new)) {
            return self::$output->withCode(21002);
        }
        $old = Crypt::pwd($old);
        $row = self::t('admin')->withWhere($adminid)->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        if ($old != $row['pwd']) {
            return self::$output->withCode(211032);
        }
        $new = Crypt::pwd($new);
        self::t('admin')->withWhere($adminid)->update(['pwd' => $new]);
        return self::$output->withCode(200);
    }

    /**
     * 管理员保存前处理
     * @param $data
     * @param array $row
     * @return array
     */
    public static function adminSaveBefore(&$data, $row = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (empty($row['id']) && empty($data['pwd'])) {
                return 223017;
            }
            if (!empty($data['userid'])) {
                $admin = self::t('admin')->withWhere(['userid' => $data['userid']])->fetch();
                if (!empty($row['id'])) {
                    if ($admin && $admin['id'] != $row['id']) {
                        return 223016;
                    }
                } else {
                    if ($admin) {
                        return 223016;
                    }
                }
            }
        }
        if (!empty($data['pwd'])) {
            $data['pwd'] = Crypt::pwd($data['pwd']);
        } else {
            unset($data['pwd']);
        }
        return 200;
    }

    /**
     * 后台操作日志
     * @param array $user
     * @return OutputInterface
     */
    public static function logSave(array $user): OutputInterface
    {
        if (self::$config['adminLog'] == '1') {
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
}
