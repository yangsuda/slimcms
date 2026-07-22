<?php
/**
 * 路由调用器 - 支持 ThinkPHP/Laravel 风格的路由注册
 *
 * 两种使用方式：
 * 1. 传统方式（在路由文件闭包内）：Route::action('Admin\LoginController@login')
 * 2. 门面方式（直接注册）：Route::get('/admin/login', 'Admin\LoginController@login')
 *
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Core;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimCMS\Error\TextException;

class RouteAction
{
    /**
     * 当前路由收集器（App 或 RouteCollectorProxy）
     */
    private static App|RouteCollectorProxy|null $collector = null;

    /**
     * 设置路由收集器（在 Routes.php 的 route() 方法中调用）
     */
    public static function setCollector(App|RouteCollectorProxy $collector): void
    {
        self::$collector = $collector;
    }

    /**
     * 获取当前路由收集器
     */
    public static function getCollector(): App|RouteCollectorProxy
    {
        if (self::$collector === null) {
            throw new \RuntimeException('Route collector not set. Call RouteAction::setCollector($app) first.');
        }
        return self::$collector;
    }

    // ================================================================
    // 门面方式路由注册（类 ThinkPHP 风格）
    // ================================================================

    public static function get(string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->get($pattern, is_callable($action) ? $action : self::action($action));
    }

    public static function post(string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->post($pattern, is_callable($action) ? $action : self::action($action));
    }

    public static function put(string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->put($pattern, is_callable($action) ? $action : self::action($action));
    }

    public static function delete(string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->delete($pattern, is_callable($action) ? $action : self::action($action));
    }

    public static function patch(string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->patch($pattern, is_callable($action) ? $action : self::action($action));
    }

    public static function any(string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->any($pattern, is_callable($action) ? $action : self::action($action));
    }

    public static function gp(string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->map(['GET', 'POST'], $pattern, is_callable($action) ? $action : self::action($action));
    }

    public static function map(array $methods, string $pattern, string|callable $action = null): \Slim\Interfaces\RouteInterface
    {
        return self::getCollector()->map($methods, $pattern, is_callable($action) ? $action : self::action($action));
    }


    /**
     * 路由分组（设置 RouteCollectorProxy 再执行）
     *
     * @param string $pattern
     * @param callable $callback
     * @return \Slim\Interfaces\RouteGroupInterface
     */
    public static function group(string $pattern, callable $callback): \Slim\Interfaces\RouteGroupInterface
    {
        return self::getCollector()->group($pattern, function (RouteCollectorProxy $group) use ($callback) {
            $previous = self::$collector;
            self::$collector = $group;
            $callback();
            self::$collector = $previous;
        });
    }

    // ================================================================
    // action() 方式（在路由文件闭包内使用，配合 $group->get() 等）
    // ================================================================

    /**
     * 返回控制器可调用对象
     *
     * @param string $action 格式：'Admin\LoginController@login'
     */
    public static function action(string $action = null): callable
    {
        return function (ServerRequestInterface $request, ResponseInterface $response, array $args = []) use ($action) {
            [$controller, $method] = self::parseAction($request, $action, $args);
            $parameters = ['request' => $request, 'response' => $response, 'app' => self::$collector];
            $instance = self::$collector->getContainer()->make($controller, [
                'request' => self::$collector->getContainer()->make(Request::class, $parameters),
                'response' => self::$collector->getContainer()->make(Response::class, $parameters)
            ]);
            return $instance->$method();
        };
    }

    /**
     * 解析动作字符串
     */
    private static function parseAction(ServerRequestInterface $request, string $action = null, array $args = []): array
    {
        if (!empty($action) && strpos($action, '@') !== false) {
            [$controller, $method] = explode('@', $action, 2);
            // 不是完整类名时，自动补全 App\Controller\
            if (strpos($controller, 'App\\') !== 0 && strpos($controller, '\\') !== 0) {
                $controller = 'App\\Controller\\' . $controller;
            }
        } else {
            $route = str_replace('/', '\\', trim($request->getAttribute('__route__')->getPattern(), '/'));
            foreach ($args as $k => $v) {
                $route = str_replace('{' . $k . '}', $v, $route);
            }
            $method = pathinfo($route, PATHINFO_BASENAME);
            $path = pathinfo($route, PATHINFO_DIRNAME);
            $className = pathinfo($path, PATHINFO_BASENAME);
            $controller = $className ? 'App\\Controller\\' . $action . pathinfo($path, PATHINFO_DIRNAME) . '\\' . ucfirst($className) . 'Controller' : '';
        }
        if (!class_exists($controller)) {
            throw new TextException(503, "Controller class not found");
        }
        if (!method_exists($controller, $method) || !(new \ReflectionMethod($controller, $method))->isPublic()) {
            throw new TextException(503, "Method {$method} not found");
        }
        return [$controller, $method];
    }
}
