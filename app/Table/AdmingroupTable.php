<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;

class AdmingroupTable extends Table
{
    /**
     * 表单HTML获取之前的自定义处理
     * @param $fields
     * @param $data
     * @param $form
     * @return int
     */
    public function getFormHtmlBefore(&$fields, &$data, &$form, &$options): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            $data['forms'] = self::t('forms')->withWhere(['jumpurl' => ''])->fetchList();
            $permissions = self::t('adminpermission')->fetchList();
            $data['permissions'] = [];
            foreach ($permissions as $v1) {
                $index = strpos($v1['purview'], '/') ? stristr($v1['purview'], '/', true) : '_';
                $data['permissions'][$index][] = $v1;
            }
            //插件中设置的权限
            $where = ['isinstall' => 1, 'available' => 1];
            $where[] = self::t()->field('permission', '', '<>');
            $data['plugin'] = self::t('plugins')->withWhere($where)->fetchList();
            foreach ($data['plugin'] as &$v) {
                $v['permission'] = unserialize($v['permission']);
            }
            $data['_purviews'] = !empty($data['purviews']) ? explode(',', $data['purviews']) : [];
        }
        return 200;
    }

    /**
     * 数据保存前的自定义处理
     * @param $data
     * @param string $row
     * @return int
     */
    public function dataSaveBefore(&$data, $row = [], $options = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (!empty($data['purviews'])) {
                $data['purviews'] = implode(',', $data['purviews']);
            }
        }
        return 200;
    }

}
