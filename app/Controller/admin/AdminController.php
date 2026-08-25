<?php

/**
 * 后台控制类
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Model\entity\AdminEntity;
use app\Service\admin\AuthService;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Error\TextException;
use SlimCMS\Interfaces\OutputInterface;

class AdminController extends ControlAbstract
{
    protected AuthService $authService;

    public function __construct(App $app, AuthService $authService)
    {
        parent::__construct($app);
        $this->authService = $authService;
    }

    /**
     * 获取管理员信息
     * @return AdminEntity|null
     */
    protected function adminInfo(): AdminEntity
    {
        return $this->request->getAttribute('admin');
    }

    /**
     * 权限检测
     * @param string|null $auth
     * @return ResponseInterface|null
     * @throws TextException
     */
    protected function checkAllow(string $auth = null): ?ResponseInterface
    {
        $auth = $auth ?? $this->p;
        $res = $this->authService->checkAllow($this->adminInfo(), $auth);
        if ($res->getCode() === 200) {
            return null;
        }
        if ($this->determineContentType() === 'application/json') {
            throw new TextException($res->getCode(), $res->getMsg());
        }
        $referer = $res->getReferer() ?: '/admin/index';
        return $this->response->withStatus(302)->withHeader('Location', $referer);
    }

    /**
     * {@inheritDoc}
     */
    public function view(OutputInterface $output = null, string $template = ''): ResponseInterface
    {
        $output = $output ?? $this->output;
        $data = [];
        $data['leftMenu'] = $this->authService->leftMenu($this->adminInfo());
        if (empty($output->getData()['admin'])) {
            $data['admin'] = $this->adminInfo()->toArray();
        }
        $output = $output->withData($data);
        return parent::view($output, $template);
    }
}
