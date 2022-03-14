<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;

class SysenumTable extends Table
{
    /**
     * 列表数据获取之前的自定义处理
     * @param $param
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function dataListBefore(&$param): int
    {
        $where = [];
        if (defined('MANAGE') && MANAGE == 1) {
            if (isset($param['get']['evalue'])) {
                $where['reid'] = $param['get']['evalue'];
                $where['egroup'] = $param['get']['egroup'];
                $where[] = self::t()->field('evalue', 0, '>');
            } else {
                $where['evalue'] = 0;
            }
        } else {
            $where[] = self::t()->field('evalue', 0, '>');
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
        if (defined('MANAGE') && MANAGE == 1) {
            $evalue = aval($param, 'get/evalue');
            $evalue && $list['reid'] = self::t('sysenum')->withWhere($evalue)->fetch();
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
            if ($data['mngtype'] == 'add') {
                $where = [];
                $where['egroup'] = $data['egroup'];
                if (!empty($data['evalue'])) {
                    $where['id'] = $data['evalue'];
                } else {
                    $where['evalue'] = 0;
                }
                $_reid = self::t('sysenum')->withWhere($where)->withOrderby('id', 'asc')->fetch();
                if ($_reid && $_reid['id'] != $data['id']) {
                    $val = [];
                    $val['evalue'] = $data['id'];
                    $val['reid'] = $_reid['evalue'] ?: 0;
                    self::t('sysenum')->withWhere($data['id'])->update($val);
                }
            }
        }
        return 200;
    }
}
