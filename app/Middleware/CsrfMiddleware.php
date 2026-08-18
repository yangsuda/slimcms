<?php
declare(strict_types=1);

namespace app\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SlimCMS\Core\Session;
use SlimCMS\Error\TextException;

class CsrfMiddleware extends \SlimCMS\Core\MiddleWare
{
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $method = strtoupper($request->getMethod());

        //1小时刷新一次csrftoken
        if (time() - $this->session->get('csrf_token_time', 0) > 3600) {
            $this->refreshCsrfToken();
        }

        // 只对修改数据的请求进行 CSRF 检查
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->validateCsrfToken($request);
        }

        // 生成新的 CSRF token（用于下次请求）
        $this->generateCsrfToken();
        // 在响应中添加 CSRF token
        $csrfToken = $this->getCsrfToken();
        $request = $request->withAttribute('csrfToken', $csrfToken);
        $response = $handler->handle($request);
        return $response->withHeader('X-CSRF-Token', $csrfToken);
    }

    /**
     * 验证 CSRF token
     */
    private function validateCsrfToken(Request $request): void
    {
        $sessionToken = $this->session->get('csrf_token', '');
        $requestToken = $this->getRequestToken($request);

        // 检查 token 是否存在
        if (empty($sessionToken) || empty($requestToken)) {
            throw new TextException(403, 'CSRF token 缺失');
        }

        // 安全比较 token（防止时序攻击）
        if (!hash_equals($sessionToken, $requestToken)) {
            throw new TextException(403, 'CSRF token 验证失败');
        }
    }

    /**
     * 从请求中获取 CSRF token
     */
    private function getRequestToken(Request $request): string
    {
        // 优先从 header 获取
        $token = $request->getHeaderLine('X-CSRF-Token');
        if (empty($token)) {
            // 其次从表单数据获取
            $parsedBody = $request->getParsedBody();
            $token = $parsedBody['csrf_token'] ?? '';
        }

        return $token;
    }

    /**
     * 生成 CSRF token
     */
    private function generateCsrfToken(): void
    {
        if (!$this->session->has('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
            $this->session->set('csrf_token_time', time());
        }
    }

    /**
     * 获取当前 CSRF token
     */
    public function getCsrfToken(): string
    {
        if (!$this->session->has('csrf_token')) {
            $this->generateCsrfToken();
        }
        return $this->session->get('csrf_token', null);
    }

    /**
     * 刷新 CSRF token
     */
    public function refreshCsrfToken(): void
    {
        $this->session->delete('csrf_token');
        $this->session->delete('csrf_token_time');
        $this->generateCsrfToken();
    }
}