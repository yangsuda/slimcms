<?php
/**
 * Session中间件
 * 统一管理Session启动，并将session数据注入请求属性
 * @author zhucy
 */
declare(strict_types=1);

namespace App\MiddleWare;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $request = $request->withAttribute('session', $_SESSION);

        return $handler->handle($request);
    }
}
