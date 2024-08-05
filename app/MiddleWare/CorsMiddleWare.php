<?php

namespace App\MiddleWare;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 跨域中间件
 * Class CorsMiddleWare
 * @package App\MiddleWare
 * @author  fo3xx@qq.com
 */
class CorsMiddleWare implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin    = $request->getServerParams()['HTTP_ORIGIN'] ?? '';
        $response  = $handler->handle($request);
        $cors_pass = false;
        foreach ([
                     '192.168.1.',
                     'localhost:',
                 ] as $domain) {
            if (is_int(stripos($origin, $domain))) {
                $cors_pass = true;
                break;
            }
        }
        if ($cors_pass) {
            $response = $response
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type,Timestamp')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        }
        return $response;
    }
}
