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
     * 数据格式输出
     * @param $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function output($result = [])
    {
        return $this->response->output($result);
    }

    /**
     * 加载模板输出
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function view($result = [])
    {
        $p = $this->input('p');
        $result['template'] = $result['template'] ?? $p;
        return $this->output($result);
    }

    /**
     * 直接跳转
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function directTo($result = [])
    {
        $result['directTo'] = 1;
        return $this->output($result);
    }

    /**
     * 跨域请求返回数据
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     *
     */
    public function jsonCallback($result = [])
    {
        $result['jsonCallback'] = 1;
        return $this->output($result);
    }

    public function __destruct()
    {
        //删除操作时临时生成的cookie提示信息
        $this->request->getCookie()->set('errorCode');
        $this->request->getCookie()->set('errorMsg');
    }

}