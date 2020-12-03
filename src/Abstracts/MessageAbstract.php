<?php
/**
 * 数据输出处理类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Abstracts;

use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use SlimCMS\Helper\Str;
use SlimCMS\Interfaces\CookieInterface;
use SlimCMS\Interfaces\OutputInterface;

abstract class MessageAbstract
{

    /**
     * 全局实例对象
     * @var App
     */
    protected $app;

    /**
     * 请求实例
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * 响应实例
     * @var ResponseInterface
     */
    protected $response;

    /**
     * 配置参数
     * @var array|mixed
     */
    protected $cfg = [];

    protected $settings = [];

    static protected $cookie;

    protected $output;

    /**
     * 容器
     * @var \DI\Container|mixed
     */
    protected $container;

    public function __construct(ServerRequestInterface $request, ResponseInterface $response, App $app)
    {
        $this->request = $request;
        $this->response = $response;
        $this->app = $app;
        $this->container = $app->getContainer()->get('DI\Container');
        $this->cfg = $this->container->get('cfg');
        $this->settings = $this->container->get('settings');
        if (empty(self::$cookie)) {
            self::$cookie = $this->container->get(CookieInterface::class);
        }
        $output = $this->container->get(OutputInterface::class);
        $output($app);
        $this->output = $output;
    }

    /**
     * 返回提示数据
     * @param $code
     * @return array
     */
    public function output(OutputInterface $output): ResponseInterface
    {
        $content = $output->analysisTemplate();
        $this->response = $this->response->withHeader('Content-type', 'text/html');
        $this->response->getBody()->write($content);
        return $this->response;
    }

    public function cookie(): CookieInterface
    {
        return self::$cookie;
    }

    /**
     * 返回请求对象
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * 返回响应对象
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * 返回全局实例对象
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
    }

    /**
     * 获取容器
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * 返回配置参数
     * @return array
     */
    public function getCfg(): array
    {
        return $this->cfg;
    }

    public function getCookie()
    {
        return self::$cookie;
    }

    public function getAuthKey()
    {
        $saltkey = self::$cookie->get('saltkey');
        if (empty($saltkey)) {
            $saltkey = Str::random(8);
            self::$cookie->set('saltkey', $saltkey, 86400 * 30);
        }
        return md5($this->settings['security']['authkey'] . $saltkey);
    }

    /**
     * 获取表单检验KEY
     * @return bool|string
     */
    public function getFormHash()
    {
        isset($_SESSION) ? '' : session_start();
        $_SESSION['formHash'] = substr(md5(uniqid()), 8, 8);
        return $_SESSION['formHash'];
    }
}