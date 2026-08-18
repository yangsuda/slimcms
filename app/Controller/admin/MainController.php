<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\RecoveryService;
use Psr\Http\Message\MessageInterface;

class MainController extends AdminController
{
    /**
     * 后台仪表盘
     * 当前管理员信息通过 AdminAuthMiddleware 注入的 request attribute 'admin' 获取
     */
    public function index(): MessageInterface
    {
        return $this->view($this->output);
    }

    /**
     * 恢复数据
     * @return MessageInterface
     */
    public function recovery()
    {
        $this->checkAllow();
        $id = $this->inputInt('id');
        $res = $this->i(RecoveryService::class)->recovery($id);
        return $this->json($res);
    }

    /**
     * 修改密码
     * @return MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function updatePwd(): MessageInterface
    {
        $this->checkAllow();
        if ($this->request->getMethod() === 'POST') {
            $formhash = $this->inputString('formhash');
            $oldpwd = $this->inputString('oldpwd');
            $newpwd = $this->inputString('newpwd');
            $res = $this->authService()->formVerify($formhash)->updatePwd($this->admin->userid, $oldpwd, $newpwd);
            return $this->directTo($res);
        }
        return $this->view($this->output);
    }
}
