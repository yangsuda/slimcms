<?php

declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Table;
use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use app\Service\admin\FormsService;

class FormsTable extends Table implements TableHookInterface
{
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
            empty($data['jumpurl']) && $this->r(FormsRepository::class)->createTable($table, $name);
        }
        return 200;
    }

    public function dataSaveAfter(array $data, array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            if ($data['mngtype'] == 'add') {
                $this->i(FormsService::class)->formInit((int)$data['id']);
            }
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
                $this->r(Forms_fieldsRepository::class)->withWhere(['formid' => $row['id']])->batchDelete();
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
