<?php

declare(strict_types=1);

namespace app\Table;

use app\Core\Table;
use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use app\Service\admin\FormsService;

class FormsTable extends Table
{
    /**
     * 自定义表单数据保存处理
     * @param $data
     * @param array $row
     * @return int
     */
    public function dataSaveBefore(&$data, $row = [], $options = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
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

    public function dataSaveAfter($data, $row = [], $options = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if ($data['mngtype'] == 'add') {
                $this->i(FormsService::class)->formInit((int)$data['id']);
            }
        }
        return 200;
    }

    /**
     * 数据删除后的自定义处理
     * @param $data
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function dataDelAfter($data, $options = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (!empty($data['id'])) {
                $this->r(Forms_fieldsRepository::class)->withWhere(['formid' => $data['id']])->batchDelete();
            }
        }
        return 200;
    }

    /**
     * 列表数据获取之前的自定义处理
     * @param $param
     * @return int
     */
    public function dataListInit(&$param)
    {
        $where = [];
        !empty($param['export']) && $where['export'] = $param['export'];
        $param['where'] = $where;
        return 200;
    }
}
