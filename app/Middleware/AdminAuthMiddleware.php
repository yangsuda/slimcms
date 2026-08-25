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
use Slim\App;
use Slim\Psr7\Factory\ResponseFactory;
use SlimCMS\Error\TextException;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Helper\Str;

class AdminAuthMiddleware implements MiddlewareInterface
{
    private App $app;
    private AdminRepository $adminRepository;
    private AdminlogRepository $adminlogRepository;
    private ResponseFactory $responseFactory;

    public function __construct(App $app, AdminRepository $adminRepository, AdminlogRepository $adminlogRepository,ResponseFactory $responseFactory)
    {
        $this->app = $app;
        $this->adminRepository = $adminRepository;
        $this->adminlogRepository = $adminlogRepository;
        $this->responseFactory = $responseFactory;
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
            $response = $this->responseFactory->createResponse(302);
            return $response->withHeader('Location', '/admin/login?referer=' . $referer);
        }

        // 3. 获取用户信息
        $adminInfo = $this->adminRepository->adminInfo((int)$adminid);
        if (empty($adminInfo)) {
            $response = $this->responseFactory->createResponse(302);
            return $response->withHeader('Location', '/admin/login');
        }
        $request = $request->withAttribute('admin', $adminInfo);
        //标记为后台请求
        $request = $request->withAttribute('adminContext', true);

        $config = $this->app->getContainer()->get('cfg');
        //日志记录
        if (!empty($config['adminLog'])) {
            $postinfo = $request->getParsedBody();
            $postinfo = $postinfo ? json_encode(Str::addslashes($postinfo)) : '';
            $postinfo = substr($postinfo, 0, 5000);
            $this->adminlogRepository->add([
                'adminid' => $adminInfo->id,
                'adminname' => $adminInfo->userid,
                'method' => aval($request->getServerParams(), 'REQUEST_METHOD'),
                'query' => substr($request->getUri()->getQuery(), 0, 500),
                'ip' => Ipdata::getip($request),
                'createtime' => TIMESTAMP,
                'postinfo' => $postinfo,
                'route' => $request->getUri()->getPath()
            ]);
        }
        return $handler->handle($request);
    }
}