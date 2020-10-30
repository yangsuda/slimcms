<?php
/**
 * 默认控制类
 */

namespace SlimCMS\Abstracts;

use SlimCMS\Interfaces\OutputInterface;

abstract class ControlAbstract extends BaseAbstract
{
    /**
     * 加载模板输出
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function view(OutputInterface $output = null, string $template = null)
    {
        $p = $this->input('p');
        $output = $output??$this->output;
        $output = $output->withTemplate($template ?? $p);
        return $this->response($output);
    }

    /**
     * 直接跳转
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function directTo(OutputInterface $output = null)
    {
        $output = $output??$this->output;
        $output->directTo = 1;
        return $this->response($output);
    }

    /**
     * 跨域请求返回数据
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     *
     */
    public function jsonCallback(OutputInterface $output = null, string $jsonCallback)
    {
        $output = $output??$this->output;
        $output->jsonCallback = $jsonCallback;
        return $this->response($output);
    }

    public function __destruct()
    {
        //删除操作时临时生成的cookie提示信息
        $this->request->getCookie()->set('errorCode');
        $this->request->getCookie()->set('errorMsg');
    }

}