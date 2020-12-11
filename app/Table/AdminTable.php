<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;
use App\Model\admincp\LoginModel;

class AdminTable extends Table
{
    /**
     * 数据获取之后的自定义处理
     * @param $data
     * @return array
     */
    public function dataViewAfter(&$data): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            unset($data['pwd']);
            !empty($data['groupid']) &&
            $data['_groupid'] = self::t('admingroup')->withWhere($data['groupid'])->fetch('id,groupname');
        }
        return 200;
    }

    /**
     * 表单HTML获取之前的自定义处理
     * @param $data
     * @return array
     */
    public function getFormHtmlBefore(&$fields, &$data, &$form): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            $data['groups'] = self::t('admingroup')->fetchList();
        }
        return 200;
    }

    /**
     * 数据保存前的自定义处理
     * @param $data
     * @return array
     */
    public function dataSaveBefore(&$data, $row = []): int
    {
        return LoginModel::adminSaveBefore($data, $row);
    }

    /**
     * 删除前检测
     * @param $data
     * @return array
     */
    public function dataDelBefore($data): int
    {
        if ($data['id'] == 1) {
            return 21051;
        }
        return 200;
    }
}
