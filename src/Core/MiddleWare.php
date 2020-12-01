<?php
declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Str;

class MiddleWare implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->QAnalysis($request);
        $response = $handler->handle($request);
        return $response;
    }

    /**
     * 解析伪静态或加密q参数
     * @param Request $request
     */
    private function QAnalysis(Request $request)
    {
        $param = $request->getQueryParams();
        $data = Str::QAnalysis(aval($param, 'q'));
        $data && $_GET = array_merge($_GET, $data);
    }
}
