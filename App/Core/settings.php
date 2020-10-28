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
use App\Core\Routes;
use SlimCMS\Core\Cookie;
use SlimCMS\Core\Output;
use SlimCMS\Core\Template;


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
    $settings = require_once CSROOT . 'config/settings.php';
    $cfg = require_once CSDATA . 'ConfigCache.php';
    $cfg += $settings;

    $cfg += [
        'referer' => aval($_SERVER, 'HTTP_REFERER'),
        'clienttype' => 0,
    ];

    //防止最后不加/导致ueditor等加载出错
    $cfg['cfg']['basehost'] = rtrim($cfg['cfg']['basehost'], '/') . '/';

    $agent = $_SERVER['HTTP_USER_AGENT'];
    if (!empty($cfg['referer']) && strpos($cfg['referer'], 'servicewechat.com')) {
        $cfg['clienttype'] = 2;//微信小程序
    } elseif (preg_match('/MicroMessenger/i', $agent)) {
        $cfg['clienttype'] = 3;//微信WAP
    } elseif (preg_match('/NetFront|iPhone|MIDP-2.0|Opera Mini|UCWEB|Android|Windows CE/i', $agent)) {
        $cfg['clienttype'] = 1;//WAP
    }

    if (strpos(aval($_SERVER, 'HTTP_ACCEPT_ENCODING'), 'gzip') === false || !function_exists('ob_gzhandler')) {
        $cfg['settings']['output']['gzip'] = false;
    }
    $containerBuilder->addDefinitions($cfg);

    $containerBuilder->addDefinitions([
        LoggerInterface::class => DI\factory(function (ContainerInterface $c) {
            //日期格式
            $dateFormat = "Y-m-d H:i:s";
            //输出格式
            $output = "[%datetime%] - [%level_name%] - [%channel%]\n%message% %context% %extra%\n";
            //创建一个格式化器
            $formatter = new LineFormatter($output, $dateFormat);

            $loggerSettings = $c->get('settings')['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
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
    ]);
};
