<?php
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\AuthService;
use app\Service\admin\PluginsService;
use Slim\App;

class PluginController extends AdminController
{
    private PluginsService $pluginsService;

    public function __construct(App $app, AuthService $authService, PluginsService $pluginsService)
    {
        parent::__construct($app, $authService);
        $this->pluginsService = $pluginsService;
    }

    /**
     * 安装插件
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function install()
    {
        if($r = $this->checkAllow()){
            return $r;
        }
        $identifier = $this->inputString('identifier');
        $voucher = $this->inputString('voucher');
        $res = $this->pluginsService->install($identifier, $voucher);
        return $this->json($res);
    }

    /**
     * 卸载插件
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function uninstall()
    {
        if($r = $this->checkAllow()){
            return $r;
        }
        $identifier = $this->inputString('identifier');
        $delTable = $this->inputInt('delTable') ? true : false; //开启 -1关，1开
        $res = $this->pluginsService->uninstall($identifier, $delTable);
        return $this->json($res);
    }

    /**
     * 插件启用开关
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function openSwitch()
    {
        if($r = $this->checkAllow()){
            return $r;
        }
        $identifier = $this->inputString('identifier');
        $switch = $this->inputInt('switch');
        $res = $this->pluginsService->openSwitch($identifier, $switch);
        return $this->json($res);
    }

    /**
     * 插件市场
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function market()
    {
        if($r = $this->checkAllow()){
            return $r;
        }
        $res = $this->pluginsService->market();
        return $this->view($res);
    }
}
