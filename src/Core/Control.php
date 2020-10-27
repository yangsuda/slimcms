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

    /**
     * 获取外部传入数据
     * @param $name
     * @param string $type
     * @return array|mixed|\都不存在时的默认值|null
     */
    public function input($name, $type = 'string')
    {
        return $this->request->input($name, $type);
    }

    /**
     *
     * @param $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function output($result)
    {
        return $this->response->output($result);
    }

    public function __destruct()
    {
        $this->request->getCookie()->set('errorCode');
        $this->request->getCookie()->set('errorMsg');
    }

}