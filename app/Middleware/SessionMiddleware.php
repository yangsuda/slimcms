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
use SlimCMS\Core\Session;

class SessionMiddleware implements MiddlewareInterface
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('session', $this->session);

        return $handler->handle($request);
    }
}
