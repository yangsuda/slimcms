<?php

namespace app\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 跨域中间件
 *
 * 规范要点：
 * - 白名单走 .env 的 CORS_ALLOW_ORIGIN 配置，逗号分隔
 * - Origin 必须精确相等匹配（hash_equals 防时序攻击），杜绝子串绕过
 * - 仅白名单命中才回写 Access-Control-Allow-Origin / Allow-Credentials
 * - OPTIONS 预检直接返回 204，不进入业务中间件管线，避免 CSRF token / ErrorLog 误触发
 */
class CorsMiddleware implements MiddlewareInterface
{
    /** @var string[] 精确 Origin 白名单 */
    private array $allowOrigins;

    private ResponseFactoryInterface $responseFactory;

    /**
     * @param string[] $allowOrigins
     */
    public function __construct(array $allowOrigins, ResponseFactoryInterface $responseFactory)
    {
        $this->allowOrigins = array_values(array_filter(
            array_map('trim', $allowOrigins),
            static fn(string $v): bool => $v !== ''
        ));
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin  = (string)($request->getServerParams()['HTTP_ORIGIN'] ?? '');
        $allowed = $this->matchOrigin($origin);

        // OPTIONS 预检请求：直接返回 204，不进入业务中间件管线
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = $this->responseFactory->createResponse(204);
            if ($allowed && $origin !== '') {
                $response = $this->applyCorsHeaders($response, $origin);
            }
            return $response;
        }

        $response = $handler->handle($request);
        if ($allowed && $origin !== '') {
            $response = $this->applyCorsHeaders($response, $origin);
        }
        return $response;
    }

    private function applyCorsHeaders(ResponseInterface $response, string $origin): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Timestamp, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Max-Age', '86400');
    }

    /**
     * 精确相等匹配（hash_equals 防时序攻击）
     */
    private function matchOrigin(string $origin): bool
    {
        if ($origin === '') {
            return false;
        }
        foreach ($this->allowOrigins as $allowed) {
            if (hash_equals((string)$allowed, $origin)) {
                return true;
            }
        }
        return false;
    }
}