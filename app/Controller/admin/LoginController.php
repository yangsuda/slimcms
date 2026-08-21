<?php
/**
 * 后台登录控制器
 * 兼容模板渲染和 JSON API 两种模式
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\AuthService;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Core\Cookie;
use SlimCMS\Helper\Crypt;

class LoginController extends ControlAbstract
{
    protected AuthService $authService;
    private Cookie $cookie;

    public function __construct(App $app, AuthService $authService, Cookie $cookie)
    {
        parent::__construct($app);
        $this->authService = $authService;
        $this->cookie = $cookie;
    }

    /**
     * 登录页面 / 登录处理
     * GET  /admin/login - 显示登录表单
     * POST /admin/login - 处理登录请求
     */
    public function login(): ResponseInterface
    {
        // POST 请求：处理登录
        if ($this->request->getMethod() === 'POST') {
            return $this->handleLogin();
        }
        // GET 请求：显示登录表单
        return $this->showLoginForm();
    }

    /**
     * 显示登录表单
     */
    private function showLoginForm(): ResponseInterface
    {
        $referer = $this->input('referer', 'url');
        // 检查是否已登录
        $admin = $this->session()->get('admin');
        $adminid = $admin && $admin->adminAuth ? Crypt::decrypt($admin->adminAuth) : null;
        if (is_numeric($adminid) && $adminid > 0) {
            // 已登录，重定向到首页
            return $this->response
                ->withHeader('Location', $referer ?: '/admin/index')
                ->withStatus(302);
        }

        //防止安装完后点登录，成功后又退回安装页面
        if ($referer && preg_match('/(install\/index.php)$/', $referer)) {
            $referer = '';
        }

        // 返回模板渲染
        $output = $this->output
            ->withCode(200)
            ->withData([
                'referer' => $referer,
            ]);
        return $this->view($output);
    }

    /**
     * 处理登录请求
     */
    private function handleLogin(): ResponseInterface
    {
        // CSRF 检查
        $formhash = $this->inputString('formhash');
        // 验证码检查
        $ccode = $this->inputString('ccode');
        // 用户名密码校验
        $userid = $this->inputString('userid');
        $pwd = $this->inputString('pwd');
        $res = $this->authService->formVerify($formhash, $ccode)->loginCheck($userid, $pwd);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }

        $this->session()->set('admin', $res->getData()['admin']);
        // 返回成功响应
        $referer = $this->inputString('referer');
        $referer = $referer ?: '/admin/index';
        $output = $res->withReferer($referer);
        return $this->json($output);
    }

    /**
     * 退出登录
     */
    public function logout(): ResponseInterface
    {
        $this->session()->delete('admin');
        $this->session()->delete('adminAuth');

        $referer = $this->cookie->get('HTTP_REFERER') ?? '';
        $referer = '/admin/login?referer=' . urlencode($referer);

        $output = $this->output->withCode(200)->withReferer($referer);
        return $this->directTo($output);
    }
}
