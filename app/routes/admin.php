<?php
/**
 * 后台管理路由配置（ThinkPHP 风格）
 * @author zhucy
 */
declare(strict_types=1);

use app\Middleware\SessionMiddleware;
use app\Middleware\AdminAuthMiddleware;
use app\Middleware\CsrfMiddleware;
use SlimCMS\Core\RouteAction as Route;
// 后台路由组
Route::group('/admin', function () {
    // 登录相关（无需认证）
    Route::gp('/login', 'admin\LoginController@login');
    Route::get('/logout', 'admin\LoginController@logout');
    Route::get('/captcha', 'main\MainController@captcha');
    Route::get('/formhash', 'main\MainController@formHash');
    Route::get('/enumsData', 'main\MainController@enumsData');

    // 需要认证的路由
    Route::group('', function () {
        Route::get('/index', 'admin\MainController@index');
        Route::gp('/updatePwd', 'admin\MainController@updatePwd');
        Route::get('/recovery', 'admin\MainController@recovery');
        Route::gp('/ueditor', 'admin\UeditorController@ueditor');
        Route::group('/plugin', function () {
            // 动态插件路由：/admin/plugin/{path1}/{path2}
            Route::gp('/{path1}/{path2}');
        });

        Route::gp('/{path1}/{path2}');
    })->add(AdminAuthMiddleware::class)->add(CsrfMiddleware::class);
})->add(SessionMiddleware::class);
