<?php
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\PluginsService;

class PluginController extends AdminController
{
    protected function pluginsService(): PluginsService
    {
        return $this->i(PluginsService::class);
    }
    /**
     * 安装插件
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function install()
    {
        $this->checkAllow();
        $identifier = $this->inputString('identifier');
        $voucher = $this->inputString('voucher');
        $res = $this->pluginsService()->install($identifier, $voucher);
        return $this->json($res);
    }

    /**
     * 卸载插件
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function uninstall()
    {
        $this->checkAllow();
        $identifier = $this->inputString('identifier');
        $delTable = $this->inputInt('delTable') ? true : false; //开启 -1关，1开
        $res = $this->pluginsService()->uninstall($identifier, $delTable);
        return $this->json($res);
    }

    /**
     * 插件启用开关
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function openSwitch()
    {
        $this->checkAllow();
        $identifier = $this->inputString('identifier');
        $switch = $this->inputInt('switch');
        $res = $this->pluginsService()->openSwitch($identifier, $switch);
        return $this->json($res);
    }

    /**
     * 插件市场
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function market()
    {
        $this->checkAllow();
        $res = $this->pluginsService()->market();
        return $this->view($res);
    }
}
