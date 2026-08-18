<?php
declare(strict_types=1);

use Slim\App;
use function DI\autowire;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SlimCMS\Helper\File;
use SlimCMS\Interfaces\RouteInterface;
use SlimCMS\Interfaces\CookieInterface;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Interfaces\TemplateInterface;
use SlimCMS\Interfaces\DatabaseInterface;
use SlimCMS\Interfaces\UploadInterface;
use app\Core\Template;
use app\Core\Upload;
use app\Core\Output;
use app\Core\Request;
use SlimCMS\Core\Cookie;
use SlimCMS\Core\Database;
use SlimCMS\Core\Redis;
use SlimCMS\Core\Routes;

return function (ContainerBuilder $containerBuilder) {
    //Session保存路径
    $sessSavePath = CSDATA . "/sessions/";
    if (is_writeable($sessSavePath) && is_readable($sessSavePath)) {
        session_save_path($sessSavePath);
    }

    $cfg = getConfig();

    // Session 安全配置 - 所有环境统一设置
    session_set_cookie_params([
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 0),
        'path' => '/',
        'domain' => $_ENV['SESSION_DOMAIN'] ?? '',
        'secure' => APP_ENV === 'production',  // 仅生产环境强制 HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // 时区设置 - 使用标准时区名称
    date_default_timezone_set('Asia/Shanghai');

    //全局变量设置
    $containerBuilder->addDefinitions($cfg);

    $containerBuilder->addDefinitions([
        LoggerInterface::class => DI\factory(function (ContainerInterface $c) {
            return File::log();
        }),
        RouteInterface::class => autowire(Routes::class),
        CookieInterface::class => function (ContainerInterface $c) {
            return new Cookie($c);
        },
        OutputInterface::class => autowire(Output::class),
        TemplateInterface::class => autowire(Template::class),
        DatabaseInterface::class => function (ContainerInterface $c) {
            return new Database($c);
        },
        Redis::class => function (ContainerInterface $c) {
            $redis = new Redis($c);
            return $redis->selectDB();
        },
        UploadInterface::class => function (App $app) {
            return new Upload($app);
        },
        \SlimCMS\Core\Session::class => function () {
            $session = new \SlimCMS\Core\Session();
            $session->start();
            return $session;
        },
        \Psr\Http\Message\ServerRequestInterface::class => function (ContainerInterface $c) {
            $serverRequestCreator = \Slim\Factory\ServerRequestCreatorFactory::create();
            return $serverRequestCreator->createServerRequestFromGlobals();
        },

        \Psr\Http\Message\ResponseInterface::class => function (ContainerInterface $c) {
            return $c->get(\Slim\App::class)->getResponseFactory()->createResponse();
        },
        // Request 和 Response 类的绑定
        Request::class => autowire(Request::class),
    ]);
};
