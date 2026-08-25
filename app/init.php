<?php

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use SlimCMS\Handlers\HttpErrorHandler;
use SlimCMS\Handlers\ShutdownHandler;
use SlimCMS\Interfaces\RouteInterface;

define('CSINC', str_replace("\\", '/', dirname(__FILE__)) . '/');
define('CSROOT', dirname(CSINC) . '/');
define('CSAPP', CSROOT . 'app/');
define('CSDATA', CSROOT . 'data/');
define('CSPUBLIC', CSROOT . 'public/');
define('CSTEMPLATE', CSROOT . 'template/');
define('CSVENDOR', CSROOT . 'vendor/');
define('TIMESTAMP', time());
define('MICROTIME', microtime(true));
define('VERSION', '6.0.0');

require_once CSROOT . 'vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(CSROOT);
$dotenv->load();

// 从环境变量读取配置
define('CORE_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

error_reporting(CORE_DEBUG ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED);

//初始化
$containerBuilder = new ContainerBuilder();

//生产环境下生成解析缓存
if (!CORE_DEBUG) {
    $containerBuilder->enableCompilation(CSDATA);
}

// 配置定义
$settings = require CSAPP . 'Core/settings.php';
$settings($containerBuilder);

//构建PHP-DI容器实例
$container = $containerBuilder->build();

//实例化应用
AppFactory::setContainer($container);
$app = AppFactory::create();
$container->set(\Slim\App::class, $app);

//中间件设置
$app->add(\app\Middleware\Middleware::class)->add(\app\Middleware\ErrorLogMiddleware::class);

//注册路由
$routes = $container->get(RouteInterface::class);
$routes($app);

$request = $container->get(\Psr\Http\Message\ServerRequestInterface::class);

// Create Error Handler
$logger = $container->get(LoggerInterface::class);
$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory, $logger);

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(CORE_DEBUG, true, true);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, CORE_DEBUG);
register_shutdown_function($shutdownHandler);

//cors middleware
$app->add(\app\Middleware\CorsMiddleware::class);

$app->run($request);
