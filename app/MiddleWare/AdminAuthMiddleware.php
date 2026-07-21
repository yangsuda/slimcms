<?php
/**
 * 后台管理员认证中间件
 * @author zhucy
 */
declare(strict_types=1);

namespace App\MiddleWare;

use App\Service\admin\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use SlimCMS\Error\TextException;
use SlimCMS\Helper\Crypt;

class AdminAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        global $app;

        $session = $request->getAttribute('session', []);
        $admin = $session['admin']['adminAuth'] ?? null;
        $adminid = $admin ? Crypt::decrypt($admin) : null;
        if (empty($adminid)) {
            $path = $request->getUri()->getPath();
            // API/JSON 请求返回标准错误响应
            $accept = $request->getHeaderLine('Accept');
            if (str_starts_with($path, '/admin/api/') || str_contains($accept, 'application/json')) {
                throw new TextException(503, '请求错误');
            }
            // Web 请求重定向到登录页
            $referer = urlencode($path . ($request->getUri()->getQuery() ? '?' . $request->getUri()->getQuery() : ''));
            $response = (new ResponseFactory())->createResponse(302);
            return $response->withHeader('Location', '/admin/login?referer=' . $referer);
        }

        // 1. 创建框架的 Request 和 Response 对象
        $slimResponse = $app->getResponseFactory()->createResponse();
        $req = new \App\Core\Request($request, $slimResponse, $app);
        $res = new \App\Core\Response($request, $slimResponse, $app);
        // 2. 直接 new AuthService，触发 BaseAbstract::__construct() 初始化静态属性
        $authService = new AuthService($req, $res);
        // 3. 调用 loginInfo 获取用户信息
        $result = $authService->loginInfo((int)$adminid);
        if ($result->getCode() != 200) {
            $response = (new ResponseFactory())->createResponse(302);
            return $response->withHeader('Location', '/admin/login');
        }
        $request = $request->withAttribute('admin', $result->getData());
        return $handler->handle($request);
    }
}
