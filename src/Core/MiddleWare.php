<?php
declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SlimCMS\Helper\Crypt;

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
        if (!empty($param['q'])) {
            if(strpos($param['q'],'-')){
                $str = urldecode(urldecode($param['q']));
                $gets = explode('_', $str);
                foreach ($gets as $v) {
                    $keyval = explode('-', $v);
                    if (!empty($keyval[1]) || $keyval[1] == '0') {
                        $key = str_replace(['&#045;', '&#095;', '&#47;'], ['-', '_', '/'], $keyval[0]);
                        $val = str_replace(['&#045;', '&#095;', '&#47;'], ['-', '_', '/'], $keyval[1]);
                        $_GET[$key] = $val;
                    }
                }
            }else{
                $q = Crypt::decrypt((string)$param['q']);
                if($q){
                    foreach ($q as $k=>$v){
                        $_GET[$k] = $v;
                    }
                }
            }
        }
    }
}
