<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\RecoveryService;
use Psr\Http\Message\ResponseInterface;

class MainController extends AdminController
{
    /**
     * 后台仪表盘
     */
    public function index(): ResponseInterface
    {
        return $this->view($this->output);
    }

    /**
     * 恢复数据
     * @return ResponseInterface
     */
    public function recovery(): ResponseInterface
    {
        if($r = $this->checkAllow()){
            return $r;
        }
        $id = $this->inputInt('id');
        $res = $this->i(RecoveryService::class)->recovery($id);
        return $this->json($res);
    }

    /**
     * 修改密码
     * @return ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function updatePwd(): ResponseInterface
    {
        if($r = $this->checkAllow()){
            return $r;
        }
        if ($this->request->getMethod() === 'POST') {
            $formhash = $this->inputString('formhash');
            $oldpwd = $this->inputString('oldpwd');
            $newpwd = $this->inputString('newpwd');
            $res = $this->authService->formVerify($formhash)->updatePwd($this->adminInfo()->userid, $oldpwd, $newpwd);
            return $this->directTo($res);
        }
        return $this->view($this->output);
    }
}
