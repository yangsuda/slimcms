<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;
use SlimCMS\Helper\Str;

class Forms_fieldsTable extends Table
{
    /**
     * 数据获取之后的自定义处理
     * @param $data
     * @return array
     */
    public function dataViewAfter(&$data): int
    {
        if (!empty($data['rules'])) {
            $data['rules'] = Str::unserializeData($data['rules']);
        }
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
        if (!empty($data['rules'])) {
            $data['rules'] = Str::serializeData($data['rules']);
        }
        if (!$row) {
            if (empty($data['identifier']) || empty($data['formid']) || empty($data['datatype'])) {
                return 21003;
            }
            if (self::t('forms_fields')->withWhere(['formid' => $data['formid'], 'identifier' => $data['identifier']])->count()) {
                return 27011;
            }
        }
        return 200;
    }

    /**
     * 数据保存后的自定义处理
     * @param $data
     * @return array
     */
    public function dataSaveAfter($data, $row = []): int
    {
        if (!empty($row['identifier']) && !empty($row['formid'])) {
            $form = self::t('forms')->withWhere($row['formid'])->fetch();
            if (in_array($row['identifier'], ['id', 'ifcheck', 'fid', 'ip', 'createtime', 'limit', 'order', 'by', 'nocache', 'field', 'condition', 'fields', 'select', 'update', 'delete', 'insert', 'where', 'distinct', 'group'])) {
                return 21051;
            }
            self::t($form['table'])->fieldUpdate($row);
        }
        return 200;
    }

    /**
     * 数据删除后的自定义处理
     * @param $data
     * @return array
     */
    public function dataDelAfter($data): int
    {
        if (!empty($data['identifier']) && !empty($data['formid'])) {
            $form = self::t('forms')->withWhere($data['formid'])->fetch();
            self::t($form['table'])->fieldDelete($data['identifier']);
        }
        return 200;
    }

    /**
     * 表单HTML获取之前的自定义处理
     * @param $data
     * @return array
     */
    public function getFormHtmlBefore(&$condition, &$data): int
    {
        if (empty($data['displayorder'])) {
            $formid = self::input('formid', 'int');
            $list = self::t('forms_fields')->withWhere(['formid' => $formid])->withLimit(1)->fetchList('displayorder');
            if (!empty($list[0]['displayorder'])) {
                $_GET['displayorder'] = $list[0]['displayorder'] - 1;
            }
        }
        return 200;
    }
}
