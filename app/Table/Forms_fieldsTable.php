<?php

declare(strict_types=1);

namespace app\Table;

use app\Core\Table;
use app\Model\entity\Forms_fieldsEntity;
use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use app\Repository\SysenumRepository;
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
    public function dataSaveBefore(&$data, $row = [], $options = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            $arr = ['id', 'ischeck', 'style', 'fid', 'ip', 'createtime', 'limit', 'order', 'by', 'nocache',
                'field', 'condition', 'fields', 'select', 'update', 'delete', 'insert', 'where', 'distinct', 'group',
                'main'];
            if (!empty($data['identifier']) && in_array($data['identifier'], $arr)) {
                return 21059;
            }
            if (!empty($data['rules'])) {
                $data['rules'] = Str::serializeData($data['rules']);
            }
            if (!$row) {
                if (empty($data['identifier']) || empty($data['formid']) || empty($data['datatype'])) {
                    return 21003;
                }
                $where = ['formid' => $data['formid'], 'identifier' => $data['identifier']];
                if ($this->r(Forms_fieldsRepository::class)->withWhere($where)->count() > 0) {
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
    public function dataSaveAfter($data, $row = [], $options = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (!empty($row['id'])) {
                $row = array_merge($row, $data);
            }
            $row = Forms_fieldsEntity::fromArray($row);
            if (!empty($row->identifier) && !empty($row->formid)) {
                $table = $this->getTableByFormId($row->formid);
                $this->r(Forms_fieldsRepository::class)->fieldUpdate($table, $row);
            }
        }
        return 200;
    }

    /**
     * 获取表名
     * @param int $formid
     * @return mixed|null
     * @throws \SlimCMS\Error\TextException
     */
    private function getTableByFormId(int $formid)
    {
        return $this->r(FormsRepository::class)->withWhere(['id' => $formid])->fetch('table')?->table;
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
            $data = Forms_fieldsEntity::fromArray($data);
            if (!empty($data->identifier) && !empty($data->formid)) {
                $table = $this->getTableByFormId($data->formid);
                $this->r(Forms_fieldsRepository::class)->fieldDelete($table, $data->identifier);
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
    public function getFormHtmlBefore(&$fields, &$data, &$form, &$options): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if (empty($data['displayorder']) && !empty($data['formid'])) {
                $displayorder = $this->r(Forms_fieldsRepository::class)
                    ->withWhere(['formid' => $data['formid']])
                    ->withOrderby('displayorder', 'asc')
                    ->fetch('displayorder')?->displayorder;
                if (!empty($displayorder)) {
                    $data['displayorder'] = $displayorder - 1;
                }
            }
            $enums = $this->r(SysenumRepository::class)
                ->withWhere(['evalue' => 0])
                ->fetchList('id,ename,evalue,egroup,reid');
            $data['enums'] = $enums ? json_decode(json_encode($enums), true) : [];
        }
        return 200;
    }
}
