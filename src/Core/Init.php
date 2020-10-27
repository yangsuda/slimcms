<?php
declare(strict_types=1);

namespace SlimCMS\Core;


use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;
use SlimCMS\Handlers\HttpErrorHandler;
use SlimCMS\Handlers\ShutdownHandler;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use SlimCMS\Interfaces\CookieInterface;
use SlimCMS\Interfaces\RouteInterface;
use SlimCMS\Helper\Str;

class Init
{
    /**
     * 初始化
     * @return Application
     */
    public static function instance()
    {
        static $object;
        if (empty($object)) {
            $object = self::setting();
        }
        return $object;
    }

    /**
     * 参数设置
     */
    private static function setting()
    {
        $containerBuilder = new ContainerBuilder();

        //生产环境下生成解析缓存
        if (!CORE_DEBUG) {
            $containerBuilder->enableCompilation(CSDATA);
        }

        // 配置定义
        $settings = require CSAPP . 'core/settings.php';
        $settings($containerBuilder);

        //构建PHP-DI容器实例
        $container = $containerBuilder->build();

        //实例化应用
        AppFactory::setContainer($container);
        $app = AppFactory::create();

        //中间件设置
        $app->add(\SlimCMS\Core\MiddleWare::class);

        //注册路由
        $routes = $container->get(RouteInterface::class);
        $routes($app);

        $setting = $container->get('settings');

        $cookie = $container->get(CookieInterface::class);
        $saltkey = $cookie->get('saltkey');
        if (empty($saltkey)) {
            $saltkey = Str::random(8);
            $cookie->set('saltkey', $saltkey, 86400 * 30);
        }
        $authkey = md5($setting['security']['authkey'] . $saltkey);
        $container->set('authkey', $authkey);
        $formhash = substr(md5(substr((string)TIMESTAMP, 0, -5) . $authkey), 8, 8);
        $container->set('formhash', $formhash);

        // Add Routing Middleware
        $app->addRoutingMiddleware();

        $serverRequestCreator = ServerRequestCreatorFactory::create();
        $request = $serverRequestCreator->createServerRequestFromGlobals();

        // Create Error Handler
        $logger = $container->get(LoggerInterface::class);
        $callableResolver = $app->getCallableResolver();
        $responseFactory = $app->getResponseFactory();
        $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory,$logger);

        // Add Error Middleware

        $errorMiddleware = $app->addErrorMiddleware(CORE_DEBUG, true, true);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        // Create Shutdown Handler
        $shutdownHandler = new ShutdownHandler($request, $errorHandler, CORE_DEBUG);
        register_shutdown_function($shutdownHandler);

        $response = $app->handle($request);
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);
    }
}
