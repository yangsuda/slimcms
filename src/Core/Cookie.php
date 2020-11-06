<?php
/**
 * 外部请求处理类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Container\ContainerInterface;
use SlimCMS\Interfaces\CookieInterface;

class Cookie implements CookieInterface
{
    private $setting;

    public function __construct(ContainerInterface $container)
    {
        $this->setting = $container->get('settings');
    }

    public function set(string $key, $value = '', int $life = 0)
    {
        $value = (string)$value;
        $cookie = &$this->setting['cookie'];
        $var = $cookie['cookiepre'] . $key;
        $_COOKIE[$var] = $value;

        if ($value == '' || $life < 0) {
            $value = '';
            $life = -1;
        }

        $life = $life > 0 ? time() + $life : ($life < 0 ? time() - 31536000 : 0);
        $secure = $_SERVER['SERVER_PORT'] == 443;
        return setcookie($var, $value, $life, $cookie['cookiepath'], $cookie['cookiedomain'], $secure, true);
    }

    public function get(string $key)
    {
        $key = $this->setting['cookie']['cookiepre'] . $key;
        return aval($_COOKIE, $key);
    }
}
