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
use App\Core\Routes;
use App\Core\Redis;
use App\Core\Template;
use SlimCMS\Core\Cookie;
use SlimCMS\Core\Output;
use SlimCMS\Core\Database;

return function (ContainerBuilder $containerBuilder) {
    //Session保存路径
    $sessSavePath = CSDATA . "/sessions/";
    if (is_writeable($sessSavePath) && is_readable($sessSavePath)) {
        session_save_path($sessSavePath);
    }

    //Session跨域设置,为方便调试，debug开启不设置
    if (CORE_DEBUG === false) {
        @session_set_cookie_params(0, '/', '.' . $this->cfg['cfg']['domain']);
    }

    //时区设置
    @date_default_timezone_set('Etc/GMT-8');

    //全局变量设置
    $containerBuilder->addDefinitions(getConfig());

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
    ]);
};
