<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;

interface CookieInterface
{
    /**
     * 设置Cookie
     * @return mixed
     */
    public function set($key, $value = '', $life = 0);

    /**
     * 获取Cookie
     * @return mixed
     */
    public function get($key);
}
