<?php
/**
 * 前台路由配置（ThinkPHP 风格）
 * @author zhucy
 */
declare(strict_types=1);

use SlimCMS\Core\RouteAction as Route;

Route::get('/', 'main\MainController@index');
Route::get('/captcha', 'main\MainController@captcha');
