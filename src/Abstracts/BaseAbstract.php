<?php
/**
 * 默认控制类
 */

declare(strict_types=1);

namespace SlimCMS\Abstracts;

use App\Core\Redis;
use slimCMS\Core\Request;
use slimCMS\Core\Response;
use SlimCMS\Core\Table;
use SlimCMS\Interfaces\OutputInterface;

abstract class BaseAbstract
{
    /**
     * 请求对象实例
     * @var Request
     */
    protected static $request;

    /**
     * 响应对象实例
     * @var Response
     */
    protected static $response;

    protected static $output;

    protected static $container;

    /**
     * redis实例
     * @var \Redis|null
     *
     */
    protected static $redis;

    /**
     * 后台配置参数
     * @var
     */
    protected static $config;

    /**
     * 站点初始化参数
     * @var
     */
    protected static $setting;

    public function __construct(Request $request, Response $response)
    {
        self::$request = $request;
        self::$response = $response;
        self::$output = self::$request->getOutput();
        self::$container = self::$request->getContainer();
        self::$redis = self::$container->get(Redis::class);
        self::$config = self::$container->get('cfg');
        self::$setting = self::$container->get('settings');
    }

    public static function t($name): Table
    {
        static $objs = [];
        $className = ucfirst($name);
        $classname = '\App\Table\\' . $className . 'Table';
        if (!class_exists($classname)) {
            $classname = 'App\Core\Table';
        }
        if (empty($objs[$name])) {
            $objs[$name] = new $classname(self::$container, $name);
        }
        return $objs[$name];
    }

    /**
     * 获取外部传入数据
     * @param $name
     * @param string $type
     * @return array|mixed|\都不存在时的默认值|null
     */
    public static function input(string $name, string $type = 'string')
    {
        return self::$request->input($name, $type);
    }

    /**
     * 数据格式输出
     * @param $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public static function response(OutputInterface $output = null)
    {
        $output = $output ?? self::$output;
        return self::$response->output($output);
    }
}