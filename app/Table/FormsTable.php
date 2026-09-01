<?php

declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Table;

class FormsTable extends Table implements TableHookInterface
{
    // 注意：此处不能构造注入 Repository —— 所有 Repository 构造期 initialize() 会经
    // t('forms') 回解析本类，构造注入将形成 DI 循环依赖，必须用 r() 运行时惰性解析
    /**
     * 自定义表单数据保存处理
     */
    public function dataSaveBefore(array &$data, array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            if (empty($data['name'])) {
                return 21003;
            }
            if (aval($row, 'id')) {
                unset($data['table']);
            } else {
                if (empty($data['table'])) {
                    return 21003;
                }
            }
            $table = (string)aval($data, 'table');
            $name = (string)aval($data, 'name');
            empty($data['jumpurl']) && $this->r('forms')->createTable($table, $name);
        }
        return 200;
    }

    /**
     * 数据删除后的自定义处理
     */
    public function dataDelAfter(array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            if (!empty($row['id'])) {
                $this->r('forms_fields')->withWhere(['formid' => $row['id']])->batchDelete();
            }
        }
        return 200;
    }

    /**
     * 列表数据获取之前的自定义处理
     */
    public function dataListInit(array &$param): int|array
    {
        $where = [];
        !empty($param['export']) && $where['export'] = $param['export'];
        $param['where'] = $where;
        return 200;
    }
}
