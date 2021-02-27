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
     * @return int
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
     * @param array $row
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function dataSaveBefore(&$data, $row = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (!empty($data['rules'])) {
                $data['rules'] = Str::serializeData($data['rules']);
            }
            if (!$row) {
                if (empty($data['identifier']) || empty($data['formid']) || empty($data['datatype'])) {
                    return 21003;
                }
                $where = ['formid' => $data['formid'], 'identifier' => $data['identifier']];
                if (self::t('forms_fields')->withWhere($where)->count()) {
                    return 27011;
                }
            }
            if ($data['datatype'] == 'stepselect') {
                if (empty($data['egroup'])) {
                    return 27012;
                }
            } else {
                $data['egroup'] = '';
            }
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
            if (!empty($row['id'])) {
                $row = array_merge($row, $data);
            }
            if (!empty($row['identifier']) && !empty($row['formid'])) {
                $form = self::t('forms')->withWhere($row['formid'])->fetch();
                $arr = ['id', 'ischeck', 'style', 'fid', 'p', 'q', 'ip', 'createtime', 'limit', 'order', 'by', 'nocache',
                    'field', 'condition', 'fields', 'select', 'update', 'delete', 'insert', 'where', 'distinct', 'group',
                    'main', 'linkurl'];
                if (in_array($row['identifier'], $arr)) {
                    return 21059;
                }
                self::t($form['table'])->fieldUpdate($row);
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
    public function dataDelAfter($data): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (!empty($data['identifier']) && !empty($data['formid'])) {
                $form = self::t('forms')->withWhere($data['formid'])->fetch();
                self::t($form['table'])->fieldDelete($data['identifier']);
            }
        }
        return 200;
    }

    /**
     * 表单HTML获取之前的自定义处理
     * @param $fields
     * @param $data
     * @param $form
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function getFormHtmlBefore(&$fields, &$data, &$form): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (empty($data['displayorder']) && !empty($data['formid'])) {
                $list = self::t('forms_fields')
                    ->withWhere(['formid' => $data['formid']])
                    ->withLimit(1)
                    ->fetchList('displayorder');
                if (!empty($list[0]['displayorder'])) {
                    $data['displayorder'] = $list[0]['displayorder'] - 1;
                }
            }
            $data['enums'] = self::t('sysenum')->withWhere(['evalue' => 0])->fetchList();
        }
        return 200;
    }
}
