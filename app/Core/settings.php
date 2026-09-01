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
use SlimCMS\Interfaces\RepositoryFactoryInterface;
use SlimCMS\Interfaces\TemplateInterface;
use SlimCMS\Interfaces\DatabaseInterface;
use SlimCMS\Interfaces\UploadInterface;
use SlimCMS\Core\Cookie;
use SlimCMS\Core\Database;
use SlimCMS\Core\Output;
use SlimCMS\Core\Redis;
use SlimCMS\Core\RepositoryFactory;
use SlimCMS\Core\Routes;
use SlimCMS\Core\Request;
use SlimCMS\Core\Upload;
use SlimCMS\Core\Template;
use SlimCMS\Core\Form\FormSchemaServiceInterface;
use SlimCMS\Core\Form\FormSchemaService;
use SlimCMS\Core\Form\FormQueryServiceInterface;
use SlimCMS\Core\Form\FormQueryService;
use SlimCMS\Core\Form\FormWriteServiceInterface;
use SlimCMS\Core\Form\FormWriteService;
use SlimCMS\Core\Form\FormViewRendererInterface;
use SlimCMS\Core\Form\FormViewRenderer;
use SlimCMS\Core\Form\FormExportServiceInterface;
use SlimCMS\Core\Form\FormExportService;
use SlimCMS\Core\Form\FormServiceBus;
use SlimCMS\Core\Form\TableHookDispatcher;
use SlimCMS\Core\Form\OrderValidator;

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
        CookieInterface::class => autowire(Cookie::class),
        OutputInterface::class => autowire(Output::class),
        TemplateInterface::class => autowire(Template::class),
        UploadInterface::class => autowire(Upload::class),
        DatabaseInterface::class => function (ContainerInterface $c) {
            return new Database($c);
        },
        RepositoryFactoryInterface::class => function (ContainerInterface $c) {
            return new RepositoryFactory($c, $c->get(\Slim\App::class));
        },
        Redis::class => function (ContainerInterface $c) {
            $redis = new Redis($c);
            return $redis->selectDB();
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
        // CORS 中间件：白名单从 .env 读取，逗号分隔，构造注入到 CorsMiddleware
        \app\Middleware\CorsMiddleware::class => function (ContainerInterface $c) {
            $raw = trim($_ENV['CORS_ALLOW_ORIGIN'] ?? '');
            $allowOrigins = $raw === '' ? [] : explode(',', $raw);
            return new \app\Middleware\CorsMiddleware($allowOrigins, $c->get(\Slim\App::class)->getResponseFactory());
        },
        // Request 和 Response 类的绑定
        Request::class => autowire(Request::class),

        // [Forms 拆分] 表单子服务接口绑定
        FormSchemaServiceInterface::class => autowire(FormSchemaService::class),
        FormQueryServiceInterface::class => autowire(FormQueryService::class),
        FormWriteServiceInterface::class => autowire(FormWriteService::class),
        FormViewRendererInterface::class => autowire(FormViewRenderer::class),
        FormExportServiceInterface::class => autowire(FormExportService::class),
        FormServiceBus::class => autowire(FormServiceBus::class),
        TableHookDispatcher::class => autowire(TableHookDispatcher::class),
        OrderValidator::class => autowire(OrderValidator::class),
    ]);
};
