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
        $p = self::input('p');
        $output = $output ?? self::$output;
        $template = $template ?? $p;
        if (empty($template)) {
            return self::response($output->withCode(21017));
        }
        $output = $output->withTemplate($template);
        return self::response($output);
    }

    /**
     * 直接跳转
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function directTo(OutputInterface $output = null)
    {
        $output = $output ?? self::$output;
        $output->directTo = 1;
        return self::response($output);
    }

    /**
     * 跨域请求返回数据
     * @param array $result
     * @return array|\Psr\Http\Message\ResponseInterface
     *
     */
    public function jsonCallback(OutputInterface $output = null, string $jsonCallback)
    {
        $output = $output ?? self::$output;
        $output->jsonCallback = $jsonCallback;
        return self::response($output);
    }

    public function __destruct()
    {
        //删除操作时临时生成的cookie提示信息
        self::$request->getCookie()->set('errorCode');
        self::$request->getCookie()->set('errorMsg');
    }

}