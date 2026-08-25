<?php

declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Table;

class SysenumTable extends Table
{
    use \SlimCMS\Traits\Table;

    /**
     * 列表数据获取之前的自定义处理
     * @param $param
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function dataListBefore(&$param): int
    {
        $where = [];
        if ($this->request->getAttribute('adminContext') === true) {
            if (isset($param['get']['evalue'])) {
                $where['reid'] = $param['get']['evalue'];
                $where['egroup'] = $param['get']['egroup'];
                $where[] = ['evalue' => ['>', 0]];
            } else {
                $where['evalue'] = 0;
            }
        } else {
            $where[] = ['evalue' => ['>', 0]];
        }

        $param['where'] = !empty($param['where']) ? array_merge($param['where'], $where) : $where;
        return 200;
    }

    /**
     * 列表数据获取之后的自定义处理
     * @param $list
     * @param $param
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function dataListAfter(&$list, $param): int
    {
        if ($this->request->getAttribute('adminContext') === true) {
            $evalue = aval($param, 'get/evalue');
            $evalue && $list['reid'] = $this->t('sysenum')->withWhere(['id' => $evalue])->fetch();
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
        if ($this->request->getAttribute('adminContext') === true) {
            if ($data['mngtype'] == 'add') {
                $where = [];
                $where['egroup'] = $data['egroup'];
                if (!empty($data['evalue'])) {
                    $where['id'] = $data['evalue'];
                } else {
                    $where['evalue'] = 0;
                }
                $_reid = $this->t('sysenum')->withWhere($where)->withOrderby('id', 'asc')->fetch();
                if ($_reid && $_reid['id'] != $data['id']) {
                    $val = [];
                    $val['evalue'] = $data['id'];
                    $val['reid'] = $_reid['evalue'] ?: 0;
                    $this->t('sysenum')->withWhere(['id' => $data['id']])->update($val);
                }
            }
        }
        return 200;
    }
}
