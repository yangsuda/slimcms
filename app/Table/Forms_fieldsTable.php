<?php

declare(strict_types=1);

namespace app\Table;

use Slim\App;
use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Redis;
use SlimCMS\Core\Table;
use app\Model\entity\Forms_fieldsEntity;
use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use app\Repository\SysenumRepository;
use SlimCMS\Helper\Str;

class Forms_fieldsTable extends Table implements TableHookInterface
{
    protected FormsRepository $formsRepository;
    protected Forms_fieldsRepository $forms_fieldsRepository;
    protected SysenumRepository $sysenumRepository;

    public function __construct(App $app, Redis $redis, FormsRepository $formsRepository, Forms_fieldsRepository $forms_fieldsRepository, SysenumRepository $sysenumRepository)
    {
        parent::__construct($app, $redis);
        $this->formsRepository = $formsRepository;
        $this->forms_fieldsRepository = $forms_fieldsRepository;
        $this->sysenumRepository = $sysenumRepository;
    }

    /**
     * 数据获取之后的自定义处理
     */
    public function dataViewAfter(array &$data, array $options): int|array
    {
        if (!empty($data['rules'])) {
            $data['rules'] = Str::unserializeData($data['rules']);
        }
        return 200;
    }

    /**
     * 数据保存前的自定义处理
     */
    public function dataSaveBefore(array &$data, array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            $arr = ['id', 'ischeck', 'style', 'fid', 'ip', 'createtime', 'limit', 'order', 'by', 'nocache',
                'field', 'condition', 'fields', 'select', 'update', 'delete', 'insert', 'where', 'distinct', 'group',
                'main'];
            if (!empty($data['identifier']) && in_array($data['identifier'], $arr)) {
                return 21059;
            }
            if (!empty($data['rules'])) {
                $data['rules'] = Str::serializeData($data['rules']);
            }
            // cs_forms_fields 表有多个 NOT NULL DEFAULT NULL 字段，MySQL 严格模式下
            // INSERT 不包含这些字段会报 1364 错误，此处补齐默认值
            !isset($data['rules']) && $data['rules'] = '';
            !isset($data['name']) && $data['name'] = '';
            !isset($data['intro']) && $data['intro'] = '';
            !isset($data['fieldlength']) && $data['fieldlength'] = 0;
            if (!$row) {
                if (empty($data['identifier']) || empty($data['formid']) || empty($data['datatype'])) {
                    return 21003;
                }
                $where = ['formid' => $data['formid'], 'identifier' => $data['identifier']];
                if ($this->forms_fieldsRepository->withWhere($where)->count() > 0) {
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
     */
    public function dataSaveAfter(array $data, array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            if (!empty($row['id'])) {
                $row = array_merge($row, $data);
            }
            $row = Forms_fieldsEntity::fromArray($row);
            if (!empty($row->identifier) && !empty($row->formid)) {
                $table = $this->getTableByFormId($row->formid);
                if (!empty($table) && $this->tableExists($table)) {
                    $this->forms_fieldsRepository->fieldUpdate($table, $row);
                }
            }
        }
        return 200;
    }

    /**
     * 获取表名
     */
    private function getTableByFormId(int $formid)
    {
        return $this->formsRepository->withWhere(['id' => $formid])->fetch('table')?->table;
    }

    /**
     * 检查数据表是否存在
     */
    private function tableExists(string $table): bool
    {
        $setting = $this->container->get('settings');
        $fullName = $setting['db']['tablepre'] . $table;
        $result = $this->db->query("SHOW TABLES LIKE ?", [$fullName])->fetch();
        return !empty($result);
    }

    /**
     * 数据删除后的自定义处理
     */
    public function dataDelAfter(array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            $row = Forms_fieldsEntity::fromArray($row);
            if (!empty($row->identifier) && !empty($row->formid)) {
                $table = $this->getTableByFormId($row->formid);
                if (!empty($table) && $this->tableExists($table)) {
                    $this->forms_fieldsRepository->fieldDelete($table, $row->identifier);
                }
            }
        }
        return 200;
    }

    /**
     * 表单HTML获取之前的自定义处理
     */
    public function getFormHtmlBefore(array &$fields, array &$row, array $form, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            if (empty($row['displayorder']) && !empty($row['formid'])) {
                $displayorder = $this->forms_fieldsRepository
                    ->withWhere(['formid' => $row['formid']])
                    ->withOrderby('displayorder', 'asc')
                    ->fetch('displayorder')?->displayorder;
                if (!empty($displayorder)) {
                    $row['displayorder'] = $displayorder - 1;
                }
            }
            $enums = $this->sysenumRepository
                ->withWhere(['evalue' => 0])
                ->fetchList('id,ename,evalue,egroup,reid');
            $row['enums'] = $enums ? json_decode(json_encode($enums), true) : [];
        }
        return 200;
    }
}
