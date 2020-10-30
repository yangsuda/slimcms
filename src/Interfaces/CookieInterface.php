<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;

interface CookieInterface
{
    /**
     * 设置Cookie
     * @return mixed
     */
    public function set(string $key, $value = '', int $life = 0);

    /**
     * 获取Cookie
     * @return mixed
     */
    public function get(string $key);
}
