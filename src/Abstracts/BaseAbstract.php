<?php
/**
 * control、model共同继承抽象类
 */

declare(strict_types=1);

namespace SlimCMS\Abstracts;

use App\Core\Redis;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use slimCMS\Core\Request;
use slimCMS\Core\Response;
use SlimCMS\Core\Table;
use SlimCMS\Error\TextException;
use SlimCMS\Helper\Crypt;
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
        if (empty(self::$config['rewriteUrl'])) {
            if (empty($path)) {
                $path = ltrim($uri->getPath(), '/');
                if (preg_match('/\.html$/', $path)) {
                    $server = self::$request->getRequest()->getServerParams();
                    $path = basename($server['SCRIPT_FILENAME']);
                }
            }
            parse_str($url, $output);
            foreach ($output as $k => $v) {
                if ($v === '') {
                    unset($output[$k]);
                }
            }
            //URL加密
            if (!empty(self::$config['urlEncrypt'])) {
                if (!empty($output['q'])) {
                    $data = Crypt::decrypt($output['q']);
                    unset($output['q']);
                    $output = array_merge($data, $output);
                }
                $url = 'q=' . Crypt::encrypt($output);
            } else {
                $url = http_build_query($output);
            }
            $url = (preg_match('/^http/', $path) ? $path : rtrim(self::$config['basehost'], '/') . '/' . $path) . '?' . $url;
            return str_replace('%27', '\'', $url);
        }

        $server = self::$request->getRequest()->getServerParams();
        $fileName = pathinfo($server['SCRIPT_FILENAME'], PATHINFO_FILENAME);

        if (empty($path)) {
            $path = ltrim(dirname($uri->getPath()), '/');
        }
        $path = str_replace(self::$config['basehost'], '', $path);
        parse_str($url, $output);
        $data = Str::QAnalysis(pathinfo($path, PATHINFO_FILENAME));
        $data['p'] = str_replace($fileName, '', dirname($path));
        $output = array_merge($data, $output);

        if (!empty($output['q'])) {
            $data = Str::QAnalysis($output['q']);
            $data && $output = array_merge($data, $output);
            unset($output['q']);
        }
        foreach ($output as $k => $v) {
            if ($v === '') {
                unset($output[$k]);
            }
        }
        if (empty($output['p'])) {
            throw new TextException(21057);
        }
        $entre = pathinfo(self::$config['entryFileName'], PATHINFO_FILENAME) != $fileName ? $fileName : '';
        $url = rtrim(self::$config['basehost'], '/') . '/' . $entre . '/' . trim($output['p'], '/') . '/';
        $jsoncallback = !empty($output['jsoncallback']);
        unset($output['p'], $output['jsoncallback']);
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

    /**
     * 日志记录
     * @param string $msg
     * @param array $context
     * @param string $dir
     * @param string $format
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected static function log(string $msg, array $context = [], string $dir = '', string $format = 'Y-m-d')
    {
        //日期格式
        $dateFormat = "Y-m-d H:i:s";
        //输出格式
        $output = "[%datetime%]\n%message% %context%\n";
        //创建一个格式化器
        $formatter = new LineFormatter($output, $dateFormat);

        $dir .= ($dir ? '_' : '') . substr(md5(self::$setting['security']['authkey']), 5, -10);

        $path = CSDATA . $dir . '/' . date($format) . '.log';
        $logger = clone self::$container->get(LoggerInterface::class);
        $handler = new StreamHandler($path, 100);
        $handler->setFormatter($formatter);
        $logger->setHandlers([$handler]);
        $logger->info($msg, $context);
    }
}