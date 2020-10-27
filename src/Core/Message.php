<?php
/**
 * 数据输出处理类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use SlimCMS\Helper\Str;
use SlimCMS\Interfaces\CookieInterface;

abstract class Message
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
     * 返回全局实例对象
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
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
        return substr(md5(substr((string)TIMESTAMP, 0, -5) . $this->getAuthKey()), 8, 8);
    }
}