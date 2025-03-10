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
     * @return int
     * @throws \SlimCMS\Error\TextException
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
     * 删除前检测
     * @param $data
     * @return int
     */
    public function dataDelBefore($data, $options = []): int
    {
        if ($data['id'] == 1) {
            return 21051;
        }
        return 200;
    }

    /**
     * 列表数据获取之前的自定义处理
     * @param $param
     * @return array
     */
    public function dataListInit(&$param)
    {
        $where = [];
        $where[] = 'id>1';
        $param['where'] = !empty($param['where']) ? array_merge($param['where'], $where) : $where;
        return 200;
    }
}
