<?php
/**
 * 默认控制类
 */

namespace SlimCMS\Abstracts;

use slimCMS\Core\Request;
use slimCMS\Core\Response;
use SlimCMS\Interfaces\OutputInterface;

abstract class BaseAbstract
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

    protected $output;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->output = $this->request->getOutput();
    }

    /**
     * 获取外部传入数据
     * @param $name
     * @param string $type
     * @return array|mixed|\都不存在时的默认值|null
     */
    public function input(string $name, string $type = 'string')
    {
        return $this->request->input($name, $type);
    }

    /**
     * 数据格式输出
     * @param $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function response(OutputInterface $output = null)
    {
        $output = $output??$this->output;
        return $this->response->output($output);
    }
}