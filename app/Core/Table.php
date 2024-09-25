<?php
/**
 * DB层数据读写类
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Core;

use SlimCMS\Helper\FileCache;

class Table extends \SlimCMS\Core\Table
{

    /**
     * 分表操作（在调用父构造函数之前调用）
     * @param string $tableName 原表名
     * @param string $index 表名后缀
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function subtable(string $tableName, string $index): bool
    {
        $subTableName = $tableName . $index;
        $cachekey = __FUNCTION__ . '_' . $subTableName;
        $data = FileCache::get($cachekey);
        if (empty($data)) {
            $db = self::t()->db();
            $settings = self::$container->get('settings');
            $tablepre = $settings['db']['tablepre'];
            if ($db->fetch("SHOW TABLES LIKE '" . $tablepre . $subTableName . "'")) {
                return false;
            }
            //防止新生成的表单自增ID不连续
            if ($db->fetch("SHOW TABLES LIKE '" . $tablepre . $tableName . ($index - 1) . "'")) {
                $sql = "show create table " . $tablepre . $tableName . ($index - 1);
                $search = $tablepre . $tableName . ($index - 1);
            } else {
                $sql = "show create table " . $tablepre . $tableName;
                $search = $tablepre . $tableName;
            }
            $row = $db->fetch($sql);
            $sql = str_replace($search, $tablepre . $subTableName, $row['Create Table']);
            $query = $db->query($sql);
            $db->affectedRows($query);
            FileCache::set($cachekey, 1, 864000000);
        }
        return true;
    }
}
