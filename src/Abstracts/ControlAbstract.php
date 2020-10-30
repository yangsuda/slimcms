<?php
/**
 * 默认控制类
 */

namespace SlimCMS\Abstracts;

abstract class ControlAbstract extends BaseAbstract
{
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