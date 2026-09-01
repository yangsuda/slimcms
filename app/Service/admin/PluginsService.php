<?php

declare(strict_types=1);

namespace app\Service\admin;

use app\Model\entity\PluginsEntity;
use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use app\Repository\PluginsRepository;
use Slim\App;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Error\TextException;
use SlimCMS\Helper\File;
use SlimCMS\Helper\FileCache;
use SlimCMS\Helper\Http;
use SlimCMS\Helper\Zip;
use SlimCMS\Interfaces\OutputInterface;

class PluginsService extends ServiceAbstract
{
    protected $setting;//站点初始化参数

    protected $pluginsRepository;
    protected $formsRepository;
    protected $forms_fieldsRepository;

    public function __construct(App $app, PluginsRepository $pluginsRepository, FormsRepository $formsRepository, Forms_fieldsRepository $forms_fieldsRepository)
    {
        parent::__construct($app);
        $this->setting = $this->container->get('settings');
        $this->pluginsRepository = $pluginsRepository;
        $this->formsRepository = $formsRepository;
        $this->forms_fieldsRepository = $forms_fieldsRepository;
    }

    /**
     * 插件参数设置
     * @param string $identifier 插件标识符
     * @param array $data 保存的数据
     * @return OutputInterface
     */
    public function setConfig(string $identifier, array $data): OutputInterface
    {
        if (empty($identifier) || empty($data)) {
            return $this->output->withCode(21002);
        }
        $this->pluginInfo($identifier);
        $configurl = $this->getPluginPath($identifier) . 'config.php';
        $str = "<?php\r\nreturn ";
        $str .= var_export($data, true) . ';';
        file_put_contents($configurl, $str);
        return $this->output->withCode(200);
    }

    /**
     * 插件参数获取
     * @param string $identifier 插件标识符
     * @return OutputInterface
     */
    public function getConfig(string $identifier): OutputInterface
    {
        if (empty($identifier)) {
            return $this->output->withCode(21002);
        }
        $this->pluginInfo($identifier);
        $configurl = $this->getPluginPath($identifier) . 'config.php';
        static $configs = [];
        if (empty($configs[$identifier]) && is_file($configurl)) {
            $configs[$identifier] = require_once $configurl;
        }
        return $this->output->withCode(200)->withData(['config' => aval($configs, $identifier, [])]);
    }

    /**
     * 获取可用插件
     * @param string $identifier 插件标识符
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private function pluginInfo(string $identifier): ?PluginsEntity
    {
        static $plugin = [];
        if (empty($identifier)) {
            return null;
        }
        if (!empty($plugin[$identifier])) {
            return $plugin[$identifier];
        }
        $row = $this->pluginsRepository->fetchByIdIdentifier($identifier);
        if ($row?->isinstall != 1 || $row?->available != 1) {
            throw new TextException(223023, '此插件不存在或尚未启用');
        }
        $plugin[$identifier] = $row;
        return $plugin[$identifier];
    }

    /**
     * 非插件中调用插件的勾子
     * @param $plugin
     * @param $method
     * @param $param
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function hook($plugin, $method, $param): OutputInterface
    {
        if (empty($plugin) || empty($method)) {
            return $this->output->withCode(21003);
        }
        $this->pluginInfo($plugin);
        $class = '\app\Service\plugin\\' . $plugin . '\\' . ucfirst($plugin) . 'Service';
        if (class_exists($class) && ($obj = $this->i($class)) && is_callable([$obj, $method])) {
            return $obj->$method($param);
        }
        return $this->output->withCode(21009);
    }

    private function getPluginPath(string $identifier): string
    {
        return CSDATA . 'plugins/' . (int)VERSION . '/' . $identifier . '/';
    }

    /**
     * 安装插件
     * @param string $identifier 插件标识符
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function install(string $identifier, string $voucher = ''): OutputInterface
    {
        if (empty($identifier)) {
            return $this->output->withCode(21002);
        }
        $pluginDir = $this->getPluginPath($identifier);
        File::mkdir($pluginDir);
        if (is_file($pluginDir . 'install.lock')) {
            return $this->output->withCode(223025);
        }
        $this->pluginInfo($identifier);
        $plugin = aval($this->_market(), $identifier);
        if (empty($plugin)) {
            return $this->output->withCode(223023);
        }
        $installzip = $pluginDir . $identifier . '.zip';

        //下载压缩包
        //兼容老的下载方式
        if (strpos($plugin['file'], '.zip')) {
            $zipData = file_get_contents($plugin['file']);
            $zipData && file_put_contents($installzip, $zipData);
        } else {
            $fileurl = $plugin['file'];
            if ($plugin['versiontype'] == 'voucher') {
                $fileurl .= '&voucher=' . $voucher;
            }
            $res = json_decode(file_get_contents($fileurl), true);
            if ($res['code'] != 200) {
                return $this->output->withCode(21000, $res['msg']);
            }
            file_put_contents($installzip, urldecode($res['data']['zip']));
        }


        if (!is_file($installzip)) {
            return $this->output->withCode(223024);
        }
        if (md5_file($installzip) != $plugin['signature']) {
            return $this->output->withCode(223028);
        }
        Zip::unpack($installzip, $pluginDir . '../');

        if (is_file($this->getPluginPath($identifier) . 'install.php')) {
            $arr = require $this->getPluginPath($identifier) . 'install.php';
            if (!empty($arr['installCheck'])) {
                $res = $arr['installCheck']();
                if ($res->getCode() != 200) {
                    return $res;
                }
            }
        }

        //插件安装记录入库
        $this->pluginsRepository->add([
            'name' => $plugin['title'],
            'identifier' => $plugin['identifier'],
            'description' => $plugin['intro'],
            'version' => $plugin['version'],
            'isinstall' => 1,
            'author' => $plugin['author'],
            'signature' => $plugin['signature'],
            'menu' => json_encode($plugin['menu']),
            'permission' => json_encode($plugin['permission']),
        ]);

        //数据库表是否存在判断
        foreach ($this->installTables($identifier) as $v) {
            if ($this->formsRepository->tableExist($v)) {
                $this->output->withCode(223026, '数据库中' . $v . '表已存在，插件安装失败');
            }
        }

        $installSQL = $pluginDir . 'install.sql';
        if (is_file($installSQL)) {
            $content = file_get_contents($installSQL);
            $content = str_replace('#@#', $this->setting['db']['tablepre'], $content);
            //创建数据表
            foreach (explode('; ', $content) as $v) {
                $v = trim($v);
                $v && $this->pluginsRepository->excuteSql($v);
            }
        }

        //安装文件复制
        is_dir($pluginDir . 'files') && File::copyDir($pluginDir . 'files', CSROOT);

        $rename = function ($dir, $source, $target) {
            if (is_file(CSTEMPLATE . $dir . $source . '.htm')) {
                rename(CSTEMPLATE . $dir . $source . '.htm', CSTEMPLATE . $dir . $target . '.htm');
            }
        };

        foreach ($this->installTables($identifier) as $k => $v) {
            $id = $this->formsRepository->withWhere(['table' => $v])->fetch('id')?->id;
            if ($id) {
                $this->forms_fieldsRepository->withWhere(['formid' => $k])->batchUpdate(['formid' => $id]);
                //模板重命名
                $rename('admin/forms/dataList/', $k, $id);
                $rename('admin/forms/dataSave/', $k, $id);
            }
        }

        //生成安装锁定文件
        file_put_contents($pluginDir . 'install.lock', TIMESTAMP);

        if (!empty($arr['install'])) {
            $arr['install']();
        }
        return $this->output->withCode(200);
    }

    /**
     * 卸载插件
     * @param string $identifier 插件标识符
     * @param bool $delTable 是否删除数据表
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function uninstall(string $identifier, bool $delTable = true): OutputInterface
    {
        if (empty($identifier)) {
            return $this->output->withCode(21002);
        }
        $row = $this->pluginInfo($identifier);
        //删除数据表
        if ($delTable === true) {
            $this->formsRepository->dropTable($this->installTables($identifier));
        }

        $this->pluginsRepository->uninstall($row->getRawAttribute('id'));

        //删除文件
        $dirs = [
            CSAPP . 'Controller/admin/plugin/' . ucfirst($identifier) . 'Controller.php',
            CSAPP . 'Controller/admin/plugin/' . $identifier . '/',
            CSAPP . 'Controller/plugin/' . ucfirst($identifier) . 'Controller.php',
            CSAPP . 'Controller/plugin/' . $identifier . '/',
            CSAPP . 'Service/plugin/' . $identifier . '/',
            CSTEMPLATE . 'admin/plugin/' . $identifier . '/',
            CSTEMPLATE . 'plugin/' . $identifier . '/',
            CSPUBLIC . 'resources/plugin/' . $identifier . '/',
        ];
        foreach ($this->installTables($identifier) as $v) {
            $id = $this->formsRepository->withWhere(['table' => $v])->fetch('id')?->id;
            if ($id) {
                $dirs[] = CSAPP . 'Table/' . ucfirst($v) . 'Table.php';
                $dirs[] = CSTEMPLATE . 'admin/forms/dataList/' . $id . '.htm';
                $dirs[] = CSTEMPLATE . 'admin/forms/dataSave/' . $id . '.htm';

                $this->formsRepository->delete($id);
                $this->forms_fieldsRepository->withWhere(['formid' => $id])->batchDelete();
            }
        }
        foreach ($dirs as $v) {
            File::delFiles($v);
        }
        $pluginDir = $this->getPluginPath($identifier);
        is_file($pluginDir . 'install.lock') && unlink($pluginDir . 'install.lock');
        if (is_file($pluginDir . 'install.php')) {
            $arr = require $pluginDir . 'install.php';
            if (!empty($arr['uninstall'])) {
                $arr['uninstall']();
            }
        }
        if (is_file($pluginDir . 'config.php')) {
            unlink($pluginDir . 'config.php');
        }

        //删除插件
        return $this->delete($identifier);
    }

    /**
     * 安装插件相关数据表
     * @param string $identifier
     * @return array
     */
    private function installTables(string $identifier): array
    {
        $pluginDir = $this->getPluginPath($identifier);
        if (is_file($pluginDir . 'install.php')) {
            $arr = require $pluginDir . 'install.php';
            if (!empty($arr['tables'])) {
                return $arr['tables'];
            }
        }
        return [];
    }

    /**
     * 插件启用开关
     * @param string $identifier 插件标识符
     * @param int $switch 开启 -1关，1开
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function openSwitch(string $identifier, int $switch = 1): OutputInterface
    {
        if (empty($identifier)) {
            return $this->output->withCode(21002);
        }
        $switch = $switch == 1 ? 1 : -1;
        $id = $this->pluginsRepository->fetchByIdIdentifier($identifier)?->id;
        if (empty($id)) {
            return $this->output->withCode(223027);
        }
        $this->pluginsRepository->openSwitch($id, $switch);
        $pluginDir = $this->getPluginPath($identifier);
        if (is_file($pluginDir . 'install.php')) {
            $arr = require $pluginDir . 'install.php';
            if (!empty($arr['openSwitch'])) {
                $arr['openSwitch']($this->installTables($identifier), $switch);
            }
        }
        return $this->output->withCode(200);
    }

    /**
     * 删除插件
     * @param string $identifier
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private function delete(string $identifier): OutputInterface
    {
        if (empty($identifier)) {
            return $this->output->withCode(21002);
        }
        $row = $this->pluginsRepository->fetchByIdIdentifier($identifier);
        if (empty($row)) {
            return $this->output->withCode(223027);
        }
        if ($row->isinstall == 1) {
            return $this->output->withCode(223030);
        }
        $this->pluginsRepository->delete($row->id);

        $pluginDir = $this->getPluginPath($identifier);
        //删除文件
        File::delFiles($pluginDir);

        if (is_file($pluginDir . 'install.php')) {
            $arr = require $pluginDir . 'install.php';
            if (!empty($arr['delete'])) {
                $arr['delete']();
            }
        }
        return $this->output->withCode(200);
    }

    /**
     * 插件市场
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function market(): OutputInterface
    {
        $plugins = self::_market();
        foreach ($plugins as &$v) {
            $v['my'] = $this->pluginsRepository->fetchByIdIdentifier($v['identifier'])?->toArray();
        }
        return $this->output->withCode(200)->withData(['list' => $plugins]);
    }

    /**
     * 插件市场
     * @return array
     */
    private function _market(): array
    {
        $cachekey = static::class . __FUNCTION__;
        $plugins = FileCache::get($cachekey);
        if (empty($plugins)) {
            $url = Http::curlGet('https://gitee.com/919579/plugin/raw/master/url' . (int)VERSION . '.txt');
            $url = Http::curlGet(trim($url));
            if ($url === false || $url === '') {
                throw new TextException(223034);
            }
            $list = json_decode($url, true);
            if (empty($list) || !is_array($list)) {
                throw new TextException(223034);
            }
            $plugins = [];
            foreach ($list as &$v) {
                $plugins[$v['identifier']] = $v;
            }
            FileCache::set($cachekey, $plugins, 3600);
        }
        return $plugins;
    }
}
