<?php

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;
use SlimCMS\Handlers\HttpErrorHandler;
use SlimCMS\Handlers\ShutdownHandler;
use SlimCMS\Interfaces\RouteInterface;

error_reporting(0);
define('CSINC', str_replace("\\", '/', dirname(__FILE__)).'/');
define('CSROOT', dirname(CSINC).'/');
define('CSAPP', CSROOT . 'App/');
define('CSDATA', CSROOT . 'data/');
define('CSPUBLIC', CSROOT . 'public/');
define('CSTEMPLATE', CSROOT . 'template/');
define('CSVENDOR', CSROOT . 'vendor/');
define('CORE_DEBUG', true); //生产环境下设置false
define('TIMESTAMP', time());
define('VERSION', '2.0');

require_once CSROOT . 'vendor/autoload.php';

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

//中间件设置
$app->add(\SlimCMS\Core\MiddleWare::class);

//注册路由
$routes = $container->get(RouteInterface::class);
$routes($app);

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