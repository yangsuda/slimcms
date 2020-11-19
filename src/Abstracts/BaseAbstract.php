<?php
/**
 * control、model共同继承抽象类
 */

declare(strict_types=1);

namespace SlimCMS\Abstracts;

use App\Core\Redis;
use slimCMS\Core\Request;
use slimCMS\Core\Response;
use SlimCMS\Core\Table;
use SlimCMS\Helper\Str;
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

    public static function t(string $name = ''): Table
    {
        static $objs = [];
        $name = $name ?: 'forms';
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
    protected static function input($name, string $type = 'string')
    {
        return self::$request->input($name, $type);
    }

    /**
     * 数据格式输出
     * @param $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    protected static function response(OutputInterface $output = null)
    {
        $output = $output ?? self::$output;
        return self::$response->output($output);
    }

    /**
     * 生成缓存KEY
     * @param $key
     * @param mixed ...$param
     * @return string
     */
    protected static function cacheKey($key, ...$param): string
    {
        return get_called_class() . ':' . $key . ':' . Str::md5key($param);
    }

    /**
     * URL处理
     * @param string $url
     * @return string
     */
    public static function url(string $url = ''): string
    {
        $uri = self::$request->getRequest()->getUri();
        if (empty($url) || preg_match('/^&/', $url)) {
            $url = $uri->getQuery() . $url;
        }
        if (strpos($url, '?') !== false) {
            list($path, $url) = explode('?', $url);
        }
        if (empty($path)) {
            $path = ltrim($uri->getPath(), '/');
        }
        parse_str($url, $output);
        foreach ($output as $k => $v) {
            if ($v === '') {
                unset($output[$k]);
            }
        }
        $url = http_build_query($output);

        if (empty(self::$config['rewriteUrl'])) {
            $url = (preg_match('/^http/', $path) ? $path : rtrim(self::$config['basehost'], '/') . '/' . $path) . '?' . $url;
            return $url;
        }

        $entre = CURSCRIPT == 'index' ? '' : CURSCRIPT . '/';
        $url = rtrim(self::$config['basehost'], '/') . '/' . $entre . trim($output['p'], '/') . '/';
        $jsoncallback = !empty($output['jsoncallback']);
        unset($output['p'], $output['q'], $output['jsoncallback']);
        if (!empty($output)) {
            $arr = [];
            foreach ($output as $k => $v) {
                $v = is_array($v) ? implode('`', $v) : $v;
                if (!empty($v) || $v == '0') {
                    $arr[] = urlencode(str_replace(['-', '_'], ['&#045;', '&#095;'], $k) . '-' . str_replace(['-', '_'], ['&#045;', '&#095;'], $v));
                }
            }
            if ($arr) {
                $val = implode('_', $arr);
                $url .= urlencode($val) . '.html';
                //方便JS中url的拼接生成URL
                $url = str_replace(['%2527%2B', '%2B%2527'], ['\'+', '+\''], $url);
            }
        }
        return $url . ($jsoncallback ? '?jsoncallback=?' : '');
    }
}