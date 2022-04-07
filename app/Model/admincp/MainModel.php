<?php

declare(strict_types=1);

namespace App\Model\admincp;

use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Interfaces\UploadInterface;

class MainModel extends ModelAbstract
{
    /**
     * 恢复数据
     * @param $id
     * @return array
     */
    public static function recovery(int $id): OutputInterface
    {
        if (empty($id)) {
            return self::$output->withCode(21002);
        }
        $row = self::t('archivedata')->withWhere($id)->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        $content = unserialize($row['content']);
        $table = self::t('forms')->withWhere($row['formid'])->fetch('table');
        self::t($table)->insert($content);
        self::t('archivedata')->withWhere($id)->delete();
        return self::$output->withCode(200, 211031);
    }

    /**
     * 删除某个附件
     * @param $param
     * @return array
     */
    public static function delImg(array $param): OutputInterface
    {
        if (empty($param['fid']) || empty($param['id']) || empty($param['identifier'])) {
            return self::$output->withCode(21002);
        }
        $tableName = self::t('forms')->withWhere($param['fid'])->fetch('table');
        $row = self::t($tableName)->withWhere($param['id'])->fetch();
        if (empty($row[$param['identifier']])) {
            return self::$output->withCode(21001);
        }
        $upload = self::$container->get(UploadInterface::class);
        $upload->uploadDel($row[$param['identifier']]);
        self::t($tableName)->withWhere($param['id'])->update([$param['identifier'] => '']);
        return self::$output->withCode(200);
    }

    /**
     * 获取开放web功能的规则
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function getOpenWebRule()
    {
        $where = ['formid' => 1, 'identifier' => 'openweb'];
        $rules = self::t('forms_fields')->withWhere($where)->fetch('rules');
        $rules = unserialize($rules);
        return self::$output->withCode(200)->withData(['rules' => $rules]);
    }
}
