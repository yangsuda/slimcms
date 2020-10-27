<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;


use Slim\App;

interface RouteInterface
{
    /**
     * 当尝试以调用函数的方式调用一个对象时，此方法会被自动调用
     * @param App $app
     * @return mixed
     */
    public function __invoke(App $app);

    /**
     * 路由解析
     * @param App $app
     * @return mixed
     */
    public function route(App $app);
}
