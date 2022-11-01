<?php
/**
 * 插件管理控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Model\plugin\PluginModel;

class PluginControl extends AdmincpControl
{
    /**
     * 安装插件
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function install()
    {
        $this->checkAllow();
        $identifier = self::inputString('identifier');
        $voucher = self::inputString('voucher');
        $res = PluginModel::install($identifier, $voucher);
        return $this->json($res);
    }

    /**
     * 卸载插件
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function unstall()
    {
        $this->checkAllow();
        $identifier = self::inputString('identifier');
        $delTable = self::inputInt('delTable') ? true : false; //开启 -1关，1开
        $res = PluginModel::unstall($identifier, $delTable);
        return $this->json($res);
    }

    /**
     * 插件启用开关
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function openSwitch()
    {
        $this->checkAllow();
        $identifier = self::inputString('identifier');
        $switch = self::inputInt('switch');
        $res = PluginModel::openSwitch($identifier, $switch);
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
        $res = PluginModel::market();
        return $this->view($res);
    }
}
