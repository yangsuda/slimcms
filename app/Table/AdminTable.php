<?php

declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Table;

class AdminTable extends Table implements TableHookInterface
{
    use \SlimCMS\Traits\Table;

    /**
     * 数据获取之后的自定义处理
     */
    public function dataViewAfter(array &$data, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            unset($data['pwd']);
            !empty($data['groupid']) &&
            $data['_groupid'] = $this->t('admingroup')->withWhere(['id' => $data['groupid']])->fetch('id,groupname');
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
        $param['where'] = !empty($param['where']) ? array_merge($param['where'], $where) : $where;
        return 200;
    }
}
