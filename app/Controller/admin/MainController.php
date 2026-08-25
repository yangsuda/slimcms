<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\AuthService;
use app\Service\admin\RecoveryService;
use Psr\Http\Message\ResponseInterface;
use Slim\App;

class MainController extends AdminController
{
    protected RecoveryService $recoveryService;

    public function __construct(App $app, AuthService $authService, RecoveryService $recoveryService)
    {
        parent::__construct($app, $authService);
        $this->recoveryService = $recoveryService;
    }

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
        if ($r = $this->checkAllow()) {
            return $r;
        }
        $id = $this->inputInt('id');
        $res = $this->recoveryService->recovery($id);
        return $this->json($res);
    }

    /**
     * 修改密码
     * @return ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function updatePwd(): ResponseInterface
    {
        if ($r = $this->checkAllow()) {
            return $r;
        }
        if ($this->request->getMethod() === 'POST') {
            $oldpwd = $this->inputString('oldpwd');
            $newpwd = $this->inputString('newpwd');
            $res = $this->authService->updatePwd($this->adminInfo()->userid, $oldpwd, $newpwd);
            return $this->directTo($res);
        }
        return $this->view($this->output);
    }
}
