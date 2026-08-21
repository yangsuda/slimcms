<?php
/**
 * 后台管理员认证中间件
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Middleware;

use app\Repository\AdminlogRepository;
use app\Repository\AdminRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use SlimCMS\Error\TextException;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Helper\Str;

class AdminAuthMiddleware implements MiddlewareInterface
{
    private \Slim\App $app;  // 声明属性

    public function __construct(\Slim\App $app)
    {
        $this->app = $app;  // 赋值，$this->app 就有了
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $admin = $session?->get('admin');
        $adminid = $admin && $admin->adminAuth ? Crypt::decrypt($admin->adminAuth) : null;
        if (empty($adminid)) {
            $path = $request->getUri()->getPath();
            // API/JSON 请求返回标准错误响应
            $accept = $request->getHeaderLine('Accept');
            if (str_starts_with($path, '/admin/api/') || str_contains($accept, 'application/json')) {
                throw new TextException(401, '请求错误');
            }
            // Web 请求重定向到登录页
            $referer = urlencode($path . ($request->getUri()->getQuery() ? '?' . $request->getUri()->getQuery() : ''));
            $response = (new ResponseFactory())->createResponse(302);
            return $response->withHeader('Location', '/admin/login?referer=' . $referer);
        }

        $adminRepository = $this->app->getContainer()->make(AdminRepository::class, ['app' => $this->app]);
        // 3. 获取用户信息
        $adminInfo = $adminRepository->adminInfo((int)$adminid);
        if (empty($adminInfo)) {
            $response = (new ResponseFactory())->createResponse(302);
            return $response->withHeader('Location', '/admin/login');
        }
        $request = $request->withAttribute('admin', $adminInfo);
        //标记为后台请求
        $request = $request->withAttribute('adminContext', true);

        $config = $this->app->getContainer()->get('cfg');
        //日志记录
        if (!empty($config['adminLog'])) {
            $adminlogRepository = $this->app->getContainer()->make(AdminlogRepository::class, ['app' => $this->app]);
            $postinfo = $request->getParsedBody();
            $postinfo = $postinfo ? json_encode(Str::addslashes($postinfo)) : '';
            $postinfo = substr($postinfo, 0, 5000);
            $adminlogRepository->add([
                'adminid' => $adminInfo->id,
                'adminname' => $adminInfo->userid,
                'method' => aval($request->getServerParams(), 'REQUEST_METHOD'),
                'query' => substr($request->getUri()->getQuery(), 0, 500),
                'ip' => Ipdata::getip(),
                'createtime' => TIMESTAMP,
                'postinfo' => $postinfo,
                'route' => $request->getUri()->getPath()
            ]);
        }
        return $handler->handle($request);
    }
}