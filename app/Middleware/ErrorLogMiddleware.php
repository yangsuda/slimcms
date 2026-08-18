<?php
declare(strict_types=1);

namespace app\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Psr7Response;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\File;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Helper\Str;

class ErrorLogMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        // 只处理 JSON 响应（HTML 响应的错误日志通过 HttpErrorHandler 已覆盖）
        $contentType = $response->getHeaderLine('Content-Type');
        if (!str_contains($contentType, 'application/json')) {
            return $response;
        }

        // 读取响应体
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        // 仅当有明确的错误码且非200时记录
        if (!$data || !isset($data['code']) || $data['code'] == 200) {
            return $response;
        }

        // 从 Request 获取请求上下文（替代原来的 $_POST/$_GET 超全局变量）
        $queryParams = $request->getQueryParams();
        $parsedBody = (array) $request->getParsedBody();
        $post = Str::htmlspecialchars($parsedBody);
        $get  = Str::htmlspecialchars($queryParams);
        $appid = aval($parsedBody, 'appid') ?: aval($queryParams, 'appid');

        $serverParams = $request->getServerParams();

        File::log('errorCode/' . date('Y') . '/' . date('m'))
            ->info('报错信息', [
                'code'       => $data['code'],
                'msg'        => $data['msg'] ?? $data['message'] ?? '',
                'route'      => $request->getUri()->getPath(),
                'query'      => $request->getUri()->getQuery(),
                'method'     => $request->getMethod(),
                'appid'      => $appid ? Crypt::decrypt($appid) : '',
                'post'       => $post,
                'get'        => $get,
                'ip'         => Ipdata::getip(),
                'user_agent' => aval($serverParams, 'HTTP_USER_AGENT'),
            ]);

        // 重建响应（因为原 body stream 已被读取）
        $newResponse = new Psr7Response($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            $newResponse = $newResponse->withHeader($name, $values);
        }
        $newResponse->getBody()->write($body);

        return $newResponse;
    }
}
