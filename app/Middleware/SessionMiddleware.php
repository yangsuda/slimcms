<?php
/**
 * Session中间件
 * 统一管理Session启动，并将session数据注入请求属性
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private \Slim\App $app;  // 声明属性

    public function __construct(\Slim\App $app)
    {
        $this->app = $app;  // 赋值，$this->app 就有了
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->app->getContainer()->get(\SlimCMS\Core\Session::class);
        $request = $request->withAttribute('session', $session);

        return $handler->handle($request);
    }
}
