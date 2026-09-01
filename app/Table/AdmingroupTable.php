<?php

declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Table;

class AdmingroupTable extends Table implements TableHookInterface
{
    use \SlimCMS\Traits\Table;

    /**
     * 表单HTML获取之前的自定义处理
     */
    public function getFormHtmlBefore(array &$fields, array &$row, array $form, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            $row['forms'] = $this->t('forms')->withWhere(['jumpurl' => ''])->fetchList();
            $permissions = $this->t('adminpermission')->fetchList();
            $row['permissions'] = [];
            foreach ($permissions as $v1) {
                $index = strpos($v1['purview'], '/') ? stristr($v1['purview'], '/', true) : '_';
                $row['permissions'][$index][] = $v1;
            }
            //插件中设置的权限
            $where = ['isinstall' => 1, 'available' => 1];
            // [SQL安全改造] 惰性条件：参数由 withWhere->implode 统一收集
            $where[] = ['permission' => ['<>', '']];
            $row['plugin'] = $this->t('plugins')->withWhere($where)->fetchList();
            foreach ($row['plugin'] as &$v) {
                $v['permission'] = json_decode($v['permission'],true);
            }
            $row['_purviews'] = !empty($row['purviews']) ? explode(',', $row['purviews']) : [];
        }
        return 200;
    }

    /**
     * 数据保存前的自定义处理
     */
    public function dataSaveBefore(array &$data, array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            if (!empty($data['purviews'])) {
                $data['purviews'] = implode(',', $data['purviews']);
            }
        }
        return 200;
    }

    /**
     * 删除前检测
     */
    public function dataDelBefore(array $row, array $options): int|array
    {
        if ($row['id'] == 1) {
            return 21051;
        }
        return 200;
    }

    /**
     * 列表数据获取之前的自定义处理
     */
    public function dataListInit(array &$param): int|array
    {
        $where = [];
        $where[] = 'id>1';
        $param['where'] = $where;
        return 200;
    }
}
