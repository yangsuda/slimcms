<?php

declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Table;

class AdmingroupTable extends Table
{
    use \SlimCMS\Traits\Table;

    /**
     * 表单HTML获取之前的自定义处理
     * @param $fields
     * @param $data
     * @param $form
     * @return int
     */
    public function getFormHtmlBefore(&$fields, &$data, &$form, &$options): int
    {
        if ($this->request->getAttribute('adminContext') === true) {
            $data['forms'] = $this->t('forms')->withWhere(['jumpurl' => ''])->fetchList();
            $permissions = $this->t('adminpermission')->fetchList();
            $data['permissions'] = [];
            foreach ($permissions as $v1) {
                $index = strpos($v1['purview'], '/') ? stristr($v1['purview'], '/', true) : '_';
                $data['permissions'][$index][] = $v1;
            }
            //插件中设置的权限
            $where = ['isinstall' => 1, 'available' => 1];
            // [SQL安全改造] 惰性条件：参数由 withWhere->implode 统一收集
            $where[] = ['permission' => ['<>', '']];
            $data['plugin'] = $this->t('plugins')->withWhere($where)->fetchList();
            foreach ($data['plugin'] as &$v) {
                $v['permission'] = json_decode($v['permission'],true);
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
        if ($this->request->getAttribute('adminContext') === true) {
            if (!empty($data['purviews'])) {
                $data['purviews'] = implode(',', $data['purviews']);
            }
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
        $param['where'] = $where;
        return 200;
    }

}
