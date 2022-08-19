<?php

/**
 * 应用模型类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\plugin;

use App\Core\Forms;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\File;
use SlimCMS\Helper\FileCache;
use SlimCMS\Helper\Zip;
use SlimCMS\Helper\Http;
use SlimCMS\Interfaces\OutputInterface;

class PluginModel extends ModelAbstract
{
    /**
     * 插件参数设置
     * @param string $identifier 插件标识符
     * @param array $data 保存的数据
     * @return OutputInterface
     */
    public static function setConfig(string $identifier, array $data): OutputInterface
    {
        if (empty($identifier) || empty($data)) {
            return self::$output->withCode(21002);
        }
        $res = self::getPlugin($identifier);
        if ($res->getCode() != 200) {
            return $res;
        }
        $configurl = CSDATA . '/plugins/' . $identifier . '/config.php';
        $str = "<?php\r\nreturn ";
        $str .= var_export($data, true) . ';';
        file_put_contents($configurl, $str);
        return self::$output->withCode(200);
    }

    /**
     * 插件参数获取
     * @param string $identifier 插件标识符
     * @return OutputInterface
     */
    public static function getConfig(string $identifier): OutputInterface
    {
        if (empty($identifier)) {
            return self::$output->withCode(21002);
        }
        $res = self::getPlugin($identifier);
        if ($res->getCode() != 200) {
            return $res;
        }
        $configurl = CSDATA . '/plugins/' . $identifier . '/config.php';
        static $configs = [];
        if (empty($configs[$identifier]) && is_file($configurl)) {
            $configs[$identifier] = require_once $configurl;
        }
        return self::$output->withCode(200)->withData(['config' => aval($configs, $identifier, [])]);
    }

    /**
     * 获取可用插件
     * @param string $identifier 插件标识符
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function getPlugin(string $identifier): OutputInterface
    {
        static $plugin = [];
        if (empty($identifier)) {
            return self::$output->withCode(21002);
        }
        if (empty($plugin[$identifier])) {
            $row = self::t('plugins')->withWhere(['identifier' => $identifier])->fetch();
            if (empty($row) || $row['isinstall'] != 1 || $row['available'] != 1) {
                return self::$output->withCode(223023);
            }
            $plugin[$identifier] = $row;
        }
        return self::$output->withCode(200)->withData(['plugin' => $plugin[$identifier]]);
    }

    /**
     * 非插件中调用插件的勾子
     * @param mixed ...$param
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function hook(...$param): OutputInterface
    {
        $list = self::t('plugins')->withWhere(['available' => 1, 'isinstall' => 1])->fetchList();
        foreach ($list as $v) {
            $class = '\App\Model\plugin\\' . $v['identifier'] . '\\' . ucfirst($v['identifier']) . 'Model';
            if (class_exists($class) && is_callable([$class, 'hook'])) {
                $key = $param[0] . '\\' . $param[1];
                unset($param[0], $param[1]);
                $hooks = $class::hook($param);
                if (!empty($hooks[$key])) {
                    $res = $hooks[$key](array_values($param));
                    if ($res->getCode() != 200) {
                        return $res;
                    }
                }
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 安装插件
     * @param string $identifier 插件标识符
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function install(string $identifier): OutputInterface
    {
        if (empty($identifier)) {
            return self::$output->withCode(21002);
        }
        $pluginDir = CSDATA . 'plugins/' . $identifier . '/';
        File::mkdir($pluginDir);
        if (is_file($pluginDir . 'install.lock')) {
            return self::$output->withCode(223025);
        }
        $row = self::t('plugins')->withWhere(['identifier' => $identifier])->fetch();
        if (!empty($row)) {
            return self::$output->withCode(223025);
        }
        $plugin = aval(self::_market(), $identifier);
        if (empty($plugin)) {
            return self::$output->withCode(223023);
        }
        $installzip = CSDATA . 'plugins/' . $identifier . '.zip';

        //下载压缩包
        $zipData = file_get_contents($plugin['file']);
        $zipData && file_put_contents($installzip, $zipData);

        if (!is_file($installzip)) {
            return self::$output->withCode(223024);
        }
        if (md5_file($installzip) != $plugin['signature']) {
            return self::$output->withCode(223028);
        }
        Zip::unpack($installzip, CSDATA . 'plugins/');

        //是否可写检测
        $res = self::writeCheck($pluginDir . 'files', CSDATA . 'plugins/');
        if ($res->getCode() != 200) {
            return $res;
        }

        //插件安装记录入库
        $data = [];
        $data['name'] = $plugin['title'];
        $data['identifier'] = $plugin['identifier'];
        $data['description'] = $plugin['intro'];
        $data['version'] = $plugin['version'];
        $data['isinstall'] = 1;
        $data['author'] = $plugin['author'];
        $data['signature'] = $plugin['signature'];
        $data['menu'] = serialize($plugin['menu']);
        $data['permission'] = serialize($plugin['permission']);
        Forms::dataSave(8, [], $data);

        //数据库表安装调整
        $db = self::t()->db();
        foreach (self::installTables($identifier) as $v) {
            $tableName = self::$setting['db']['tablepre'] . $v;
            if ($db->fetch("SHOW TABLES LIKE '" . $tableName . "'")) {
                return self::$output->withCode(223026, ['msg' => $tableName]);
            }
        }

        $installSQL = $pluginDir . 'install.sql';
        if (is_file($installSQL)) {
            $content = file_get_contents($installSQL);
            $content = str_replace('#@#', self::$setting['db']['tablepre'], $content);
            //创建数据表
            foreach (explode('; ', $content) as $v) {
                $v = trim($v);
                $v && $db->query($v);
            }
        }

        //安装文件复制
        is_dir($pluginDir . 'files') && File::copyDir($pluginDir . 'files', CSROOT);

        $rename = function ($dir, $source, $target) {
            if (is_file(CSTEMPLATE . $dir . $source . '.htm')) {
                rename(CSTEMPLATE . $dir . $source . '.htm', CSTEMPLATE . $dir . $target . '.htm');
            }
        };

        foreach (self::installTables($identifier) as $k => $v) {
            $id = self::t('forms')->withWhere(['table' => $v])->fetch('id');
            self::t('forms_fields')->withWhere(['formid' => $k])->update(['formid' => $id]);

            //模板重命名
            $rename('admincp/forms/dataList/', $k, $id);
            $rename('admincp/forms/dataSave/', $k, $id);
            $rename('main/forms/dataList/', $k, $id);
            $rename('main/forms/dataSave/', $k, $id);
            $rename('main/forms/dataView/', $k, $id);
        }

        //生成安装锁定文件
        file_put_contents($pluginDir . 'install.lock', TIMESTAMP);

        if (is_file(CSDATA . 'plugins/' . $identifier . '/install.php')) {
            $arr = require CSDATA . 'plugins/' . $identifier . '/install.php';
            if (!empty($arr['install'])) {
                $arr['install']();
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 插件安装是否可写入校验
     * @param string $sourceDir 源文件夹
     * @param string $targetDir 目标文件夹
     * @return OutputInterface
     */
    private static function writeCheck(string $sourceDir, string $targetDir): OutputInterface
    {
        if (!is_dir($sourceDir)) {
            return self::$output->withCode(21002);
        }
        $dh = @dir($sourceDir);
        File::mkdir($targetDir);
        while (($file = $dh->read()) !== false) {
            if ($file != "." && $file != ".." && is_dir($targetDir . '/' . $file)) {
                if (is_writeable($targetDir . '/' . $file)) {
                    return self::writeCheck($sourceDir . '/' . $file, $targetDir . '/' . $file);
                } else {
                    return self::$output->withCode(223029, ['msg' => $file]);
                }
            }
        }
        $dh->close();
        return self::$output->withCode(200);
    }

    /**
     * 卸载插件
     * @param string $identifier 插件标识符
     * @param bool $delTable 是否删除数据表
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function unstall(string $identifier, bool $delTable = true): OutputInterface
    {
        if (empty($identifier)) {
            return self::$output->withCode(21002);
        }
        $row = self::t('plugins')->withWhere(['identifier' => $identifier])->fetch();
        if (empty($row)) {
            return self::$output->withCode(223027);
        }
        //删除数据表
        if ($delTable === true) {
            $db = self::t()->db();
            foreach (self::installTables($identifier) as $v) {
                $tableName = self::$setting['db']['tablepre'] . $v;
                $db->query('DROP TABLE IF EXISTS `' . $tableName . '`');
            }
        }

        self::t('plugins')->withWhere($row['id'])->update(['isinstall' => -1, 'available' => -1]);

        //删除文件
        $dirs = [
            CSAPP . 'Control/admincp/plugin/' . ucfirst($identifier) . 'Control.php',
            CSAPP . 'Control/main/plugin/' . ucfirst($identifier) . 'Control.php',
            CSAPP . 'Model/plugin/' . $identifier . '/',
            CSTEMPLATE . 'admincp/plugin/' . $identifier . '/',
            CSTEMPLATE . 'main/plugin/' . $identifier . '/',
            CSPUBLIC . 'resources/plugin/' . $identifier . '/',
        ];
        foreach (self::installTables($identifier) as $v) {
            $id = self::t('forms')->withWhere(['table' => $v])->fetch('id');
            $dirs[] = CSAPP . 'Table/' . ucfirst($v) . 'Table.php';
            $dirs[] = CSTEMPLATE . 'admincp/forms/dataList/' . $id . '.htm';
            $dirs[] = CSTEMPLATE . 'admincp/forms/dataSave/' . $id . '.htm';
            $dirs[] = CSTEMPLATE . 'main/forms/dataList/' . $id . '.htm';
            $dirs[] = CSTEMPLATE . 'main/forms/dataSave/' . $id . '.htm';
            $dirs[] = CSTEMPLATE . 'main/forms/dataView/' . $id . '.htm';

            self::t('forms')->withWhere($id)->delete();
            self::t('forms_fields')->withWhere(['formid' => $id])->delete();
        }
        foreach ($dirs as $v) {
            File::delFiles($v);
        }

        unlink(CSDATA . 'plugins/' . $identifier . '/install.lock');
        if (is_file(CSDATA . 'plugins/' . $identifier . '/install.php')) {
            $arr = require CSDATA . 'plugins/' . $identifier . '/install.php';
            if (!empty($arr['unstall'])) {
                $arr['unstall']();
            }
        }
        if (is_file(CSDATA . 'plugins/' . $identifier . '/config.php')) {
            unlink(CSDATA . 'plugins/' . $identifier . '/config.php');
        }

        //删除插件
        return self::delete($identifier);
    }

    /**
     * 安装插件相关数据表
     * @param string $identifier
     * @return array
     */
    private static function installTables(string $identifier): array
    {
        if (is_file(CSDATA . 'plugins/' . $identifier . '/install.php')) {
            $arr = require CSDATA . 'plugins/' . $identifier . '/install.php';
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
    public static function openSwitch(string $identifier, int $switch = 1): OutputInterface
    {
        if (empty($identifier)) {
            return self::$output->withCode(21002);
        }
        $switch = $switch == 1 ? 1 : -1;
        $row = self::t('plugins')->withWhere(['identifier' => $identifier])->fetch();
        if (empty($row)) {
            return self::$output->withCode(223027);
        }
        self::t('plugins')->withWhere($row['id'])->update(['available' => $switch]);

        if (is_file(CSDATA . 'plugins/' . $identifier . '/install.php')) {
            $arr = require CSDATA . 'plugins/' . $identifier . '/install.php';
            if (!empty($arr['openSwitch'])) {
                $arr['openSwitch'](self::installTables($identifier), $switch);
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 删除插件
     * @param string $identifier
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function delete(string $identifier): OutputInterface
    {
        if (empty($identifier)) {
            return self::$output->withCode(21002);
        }
        $row = self::t('plugins')->withWhere(['identifier' => $identifier])->fetch();
        if (empty($row)) {
            return self::$output->withCode(223027);
        }
        if ($row['isinstall'] == 1) {
            return self::$output->withCode(223030);
        }
        self::t('plugins')->withWhere($row['id'])->delete();

        //删除文件
        File::delFiles(CSDATA . 'plugins/' . $identifier . '/');

        if (is_file(CSDATA . 'plugins/' . $identifier . '/install.php')) {
            $arr = require CSDATA . 'plugins/' . $identifier . '/install.php';
            if (!empty($arr['delete'])) {
                $arr['delete']();
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 插件市场
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function market()
    {
        $plugins = self::_market();
        foreach ($plugins as &$v) {
            $v['my'] = self::t('plugins')->withWhere(['identifier' => $v['identifier']])->fetch();
        }
        return self::$output->withCode(200)->withData(['list' => $plugins, 'fid' => 8]);
    }

    /**
     * 插件市场
     * @return array|null
     */
    private static function _market()
    {
        $cachekey = get_class() . __FUNCTION__;
        $plugins = FileCache::get($cachekey);
        if (empty($plugins)) {
            $url = Http::curlGet('https://gitee.com/919579/plugin/raw/master/url.txt');
            $url = Http::curlGet(trim($url));
            $list = json_decode($url, true);
            $plugins = [];
            foreach ($list as &$v) {
                $plugins[$v['identifier']] = $v;
            }
            FileCache::set($cachekey, $plugins, 3600);
        }
        return $plugins;
    }
}
