<?php
/**
 * 后台登录控制器
 * 兼容模板渲染和 JSON API 两种模式
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Controller\admin;

use App\Core\Csrf;
use App\Core\Forms;
use App\Service\admin\AuthService;
use Psr\Http\Message\ResponseInterface;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\ImageCode;

class LoginController extends ControlAbstract
{
    /**
     * 登录页面 / 登录处理
     * GET  /admin/login - 显示登录表单
     * POST /admin/login - 处理登录请求
     */
    public function login(): ResponseInterface
    {
        // POST 请求：处理登录
        if (self::$request->getRequest()->getMethod() === 'POST') {
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $referer = self::input('referer', 'url');
        // 检查是否已登录
        $session = self::$request->getRequest()->getAttribute('session', []);
        $admin = $session['admin']['adminAuth'] ?? null;
        $adminid = $admin ? Crypt::decrypt($admin) : null;
        if (is_numeric($adminid) && $adminid > 0) {
            // 已登录，重定向到首页
            return self::$response->getResponse()
                ->withHeader('Location', $referer ?: '/admin/index')
                ->withStatus(302);
        }

        //防止安装完后点登录，成功后又退回安装页面
        if (preg_match('/(install\/index.php)$/', $referer)) {
            $referer = '';
        }

        // 返回模板渲染
        $output = self::$output
            ->withCode(200)
            ->withData([
                'referer' => $referer,
                'csrfToken' => Csrf::getToken()
            ]);
        return $this->view($output);
    }

    /**
     * 处理登录请求
     */
    private function handleLogin(): ResponseInterface
    {
        // CSRF 检查
        $formhash = self::inputString('formhash');
        $res = Forms::submitCheck($formhash);
        if ($res->getCode() != 200) {
            return self::response($res);
        }
        // 验证码检查
        $ccode = self::inputString('ccode');
        if (ImageCode::checkCode($ccode) === false) {
            return self::response(self::$output->withCode(24023));
        }
        // 用户名密码校验
        $userid = self::inputString('userid');
        $pwd = self::inputString('pwd');
        $res = AuthService::instance()->loginCheck($userid, $pwd);
        if ($res->getCode() != 200) {
            return self::response($res);
        }

        $_SESSION['admin'] = $res->getData();
        // 返回成功响应
        $referer = self::inputString('referer');
        $referer = $referer ?: '/admin/index';
        $output = $res->withReferer($referer);
        return self::response($output);
    }

    /**
     * 退出登录
     */
    public function logout(): ResponseInterface
    {
        unset($_SESSION['adminAuth']);
        unset($_SESSION['admin']);

        $referer = self::$request->cookie()->get('HTTP_REFERER') ?? '';
        $referer = '/admin/login?referer=' . urlencode($referer);

        $output = self::$output->withCode(200)->withReferer($referer);
        return self::directTo($output);
    }
}
