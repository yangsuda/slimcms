<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Forms;
use App\Core\Table;
use App\Model\admincp\ApimanageModel;

class FormsTable extends Table
{
    /**
     * 自定义表单数据保存处理
     * @param $data
     * @param array $row
     * @return int
     */
    public function dataSaveBefore(&$data, $row = []): int
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
            empty($data['jumpurl']) && Forms::createTable($table);
        }
        return 200;
    }

    /**
     * 数据保存后的自定义处理
     * @param $data
     * @param array $row
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function dataSaveAfter($data, $row = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            ApimanageModel::formApiManage((int)$data['id']);
        }
        return 200;
    }

    /**
     * 数据删除后的自定义处理
     * @param $data
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function dataDelAfter($data): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (!empty($data['id'])) {
                self::t('forms_fields')->withWhere(['formid' => $data['id']])->delete();
            }
        }
        return 200;
    }
}
