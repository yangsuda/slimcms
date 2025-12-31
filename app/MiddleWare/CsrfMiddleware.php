<?php
declare(strict_types=1);

namespace App\MiddleWare;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SlimCMS\Error\TextException;

class CsrfMiddleware extends \SlimCMS\Core\MiddleWare
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // 启动会话（如果尚未启动）
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $method = strtoupper($request->getMethod());
        
        // 只对修改数据的请求进行 CSRF 检查
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->validateCsrfToken($request);
        }
        
        // 生成新的 CSRF token（用于下次请求）
        $this->generateCsrfToken();
        
        $response = $handler->handle($request);
        
        // 在响应中添加 CSRF token
        $csrfToken = $this->getCsrfToken();
        return $response->withHeader('X-CSRF-Token', $csrfToken);
    }
    
    /**
     * 验证 CSRF token
     */
    private function validateCsrfToken(Request $request): void
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $requestToken = $this->getRequestToken($request);
        
        // 检查 token 是否存在
        if (empty($sessionToken) || empty($requestToken)) {
            throw new TextException(403, 'CSRF token 缺失');
        }
        
        // 安全比较 token（防止时序攻击）
        if (!hash_equals($sessionToken, $requestToken)) {
            throw new TextException(403, 'CSRF token 验证失败');
        }
        
        // 验证 token 是否过期（可选，1小时过期）
        /*$tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        if (time() - $tokenTime > 3600) {
            throw new TextException(403, 'CSRF token 已过期');
        }*/
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
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
    }
    
    /**
     * 获取当前 CSRF token
     */
    public function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $this->generateCsrfToken();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 刷新 CSRF token
     */
    public function refreshCsrfToken(): void
    {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        $this->generateCsrfToken();
    }
}