<?php

/**
 * 认证业务实现
 * @author zhucy
 */

declare(strict_types=1);

namespace app\Service\admin;

use app\Model\entity\AdminEntity;
use app\Repository\AdminloginlogRepository;
use app\Repository\AdminRepository;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Interfaces\OutputInterface;

class AuthService extends ServiceAbstract
{
    use \SlimCMS\Traits\Form;

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
            return $this->output->withCode(21002);
        }
        if ($this->output->getCode() != 200) {
            return $this->output;
        }
        $admin = $this->getAdmin()->withWhere(['userid' => $userid, 'status' => 1])->fetch('id,pwd');
        if (empty($admin)) {
            return $this->output->withCode(21001);
        }
        if ($this->getAdminloginlog()->withWhere(['userid' => $userid, 'start' => TIMESTAMP - 3600])->count() >= 3) {
            return $this->output->withCode(223014);
        }
        $ip = Ipdata::getip();
        if (!Crypt::pwdVerify($pwd, $admin->pwd)) {
            $this->getAdminloginlog()->add(['userid' => $userid, 'pwd' => $pwd, 'ip' => $ip]);
            return $this->output->withCode(211032);
        }
        $this->getAdmin()->update($admin->id, ['loginip' => $ip, 'logintime' => TIMESTAMP]);
        $admin = $this->getAdmin()->adminInfo($admin->id);
        return $this->output->withCode(200)->withData(['admin' => $admin]);
    }

    private function getAdmin(): ?AdminRepository
    {
        return $this->r(AdminRepository::class);
    }

    private function getAdminloginlog(): ?AdminloginlogRepository
    {
        return $this->r(AdminloginlogRepository::class);
    }

    /**
     * 检验用户是否有权使用某功能
     * @param AdminEntity $user
     * @param string $n
     * @return bool
     */
    private function allow(AdminEntity $user, string $n = ''): bool
    {
        if (empty($user->groupidEntity())) {
            return false;
        }
        if (empty($n) || $user->groupidEntity()->isSuperAdmin() || $user->groupidEntity()->hasPurview($n)) {
            return true;
        }
        return false;
    }

    /**
     * 权限检测
     * @param AdminEntity $user
     * @param string $n
     * @return OutputInterface
     */
    public function checkAllow(AdminEntity $user, string $n): OutputInterface
    {
        $isallow = $this->allow($user, $n);
        if (!$isallow) {
            return $this->output->withCode(21048);
        }
        return $this->output->withCode(200);
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
            return $this->output->withCode(21002);
        }
        if (!preg_match('/^(?![\d]+$)(?![a-zA-Z]+$)(?![^\da-zA-Z]+$).{6,32}$/i', $newpwd)) {
            return $this->output->withCode(223032);
        }
        $res = $this->loginCheck($userid, $pwd);
        if ($res->getCode() != 200) {
            return $res;
        }
        $this->getAdmin()->update((int)$res->getData()['admin']->id, ['pwd' => Crypt::pwd($newpwd)]);
        return $this->output->withCode(200)->withReferer('/admin/logout');
    }
}
