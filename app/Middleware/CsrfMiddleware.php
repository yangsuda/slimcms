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
    private const POOL_SIZE = 10;       // 滑动窗口：保留最近 10 个有效 token
    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $method = strtoupper($request->getMethod());
        if ($method === 'OPTIONS') {
            return $handler->handle($request);
        }

        // 先验证（POST 类），再刷新 —— 修复时序 bug
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->validateCsrfToken($request);
        }

        // 每次进入页面都生成新 token，加入令牌池
        if (in_array($method, ['POST', 'GET', 'HEAD'])) {
            $this->rotateCsrfToken();
        }

        $csrfToken = $this->getCsrfToken();
        $request = $request->withAttribute('csrfToken', $csrfToken);
        $response = $handler->handle($request);
        return $response->withHeader('X-CSRF-Token', $csrfToken);
    }

    private function validateCsrfToken(Request $request): void
    {
        $pool = $this->session->get('csrf_token_pool', []);
        $requestToken = $this->getRequestToken($request);

        if (empty($pool) || empty($requestToken)) {
            throw new TextException(403, 'CSRF token 缺失');
        }

        foreach ($pool as $entry) {
            $stored = $entry['token'] ?? '';
            if (hash_equals($stored, $requestToken)) {
                // 验证通过即可，不消费 token
                // token 靠滑动窗口自然淘汰：每次 GET 生成新 token 入池，
                // 超过 POOL_SIZE 时最旧的自动丢弃
                return;
            }
        }
        throw new TextException(403, 'CSRF token 验证失败');
    }

    private function rotateCsrfToken(): void
    {
        $token = bin2hex(random_bytes(32));
        $pool = $this->session->get('csrf_token_pool', []);
        $pool[] = ['token' => $token, 'uses' => 0];
        // 滑动窗口，只保留最近 N 个，防止无限增长
        if (count($pool) > self::POOL_SIZE) {
            $pool = array_slice($pool, -self::POOL_SIZE);
        }
        $this->session->set('csrf_token_pool', $pool);
        $this->session->set('csrf_token', $token);   // 当前页面用的 token
        $this->session->set('csrf_token_time', time());
    }

    public function getCsrfToken(): string
    {
        if (!$this->session->has('csrf_token')) {
            $this->rotateCsrfToken();
        }
        return $this->session->get('csrf_token', '');
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
}