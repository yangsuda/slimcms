<?php
/**
 * 默认控制类
 */

namespace SlimCMS\Core;

abstract class Control
{
    /**
     * 请求对象实例
     * @var Request
     */
    protected $request;

    /**
     * 响应对象实例
     * @var Response
     */
    protected $response;

    protected $requestData = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function __destruct()
    {
        $this->request->getCookie()->set('errorCode');
        $this->request->getCookie()->set('errorMsg');
    }

}