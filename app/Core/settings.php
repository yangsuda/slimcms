<?php
declare(strict_types=1);

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
use App\Core\Redis;
use App\Core\Template;
use App\Core\Upload;
use App\Core\Output;
use SlimCMS\Core\Cookie;
use SlimCMS\Core\Database;
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
        'csrf.token' => DI\factory(function () {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['csrf_token_time'] = time();
            }
            return $_SESSION['csrf_token'];
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
        UploadInterface::class => function (ContainerInterface $c) {
            //return new \App\Model\aliyun\AliOss();
            return new Upload();
        },
    ]);
};
