<?php
declare(strict_types=1);

use function DI\autowire;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Formatter\LineFormatter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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

    //Session跨域设置,为方便调试，本地注释掉
    //@session_set_cookie_params(0,'/','.'.$this->cfg['cfg']['domain']);

    //时区设置
    @date_default_timezone_set('Etc/GMT-8');

    //全局变量设置
    $containerBuilder->addDefinitions(getConfig());

    $containerBuilder->addDefinitions([
        LoggerInterface::class => DI\factory(function (ContainerInterface $c) {
            $settings = $c->get('settings');
            $fileName = substr(md5($settings['security']['authkey']), 5, -10);
            //日期格式
            $dateFormat = "Y-m-d H:i:s";
            //输出格式
            $output = "[%datetime%] - [%level_name%] - [%channel%]\n%message% %context% %extra%\n";
            //创建一个格式化器
            $formatter = new LineFormatter($output, $dateFormat);

            $path = CSDATA . 'logs_'.$fileName.'/' . date('Y-m-d') . '.log';
            $logger = new Logger('slimCMS');

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($path, 100);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);

            return $logger;
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
