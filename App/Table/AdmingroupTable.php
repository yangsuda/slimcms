<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;

class AdmingroupTable extends Table
{
    /**
     * 表单HTML获取之前的自定义处理
     * @param $data
     * @return array
     */
    public function getFormHtmlBefore(&$fields, &$data, &$form): int
    {
        $data['forms'] = self::t('forms')->fetchList();
        $data['permissions'] = self::t('adminpermission')->fetchList();
        $data['_purviews'] = !empty($data['purviews']) ? explode(',', $data['purviews']) : [];
        return 200;
    }

    /**
     * 数据保存前的自定义处理
     * @param $data
     * @param $row
     * @return array
     */
    public function dataSaveBefore(&$data, $row = ''): int
    {
        if (!empty($data['purviews'])) {
            $data['purviews'] = implode(',', $data['purviews']);
        }
        return 200;
    }

}
