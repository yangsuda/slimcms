<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;

class SysenumTable extends Table
{
    /**
     * 列表数据获取之前的自定义处理
     * @param $param
     * @return array
     */
    public function dataListBefore(&$param)
    {
        $where = [];
        if (defined('MANAGE') && MANAGE == 1) {
            if (!empty($param['get']['evalue'])) {
                $where['reid'] = $param['get']['evalue'];
                $where['egroup'] = self::input('egroup');
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
     * @return array
     */
    public function dataListAfter(&$list, $param)
    {
        if (defined('MANAGE') && MANAGE == 1) {
            /*$evalue = self::input('evalue', 'int');
            $list['reid'] = self::t('sysenum')->withWhere($evalue)->fetch();*/
        }
        return 200;
    }

    /**
     * 数据保存后的自定义处理
     * @param $data
     * @return array
     */
    public function dataSaveAfter($data, $row = [])
    {
        if ($data['mngtype'] == 'add') {
            $where = [];
            $where['egroup'] = $data['egroup'];
            if (!empty($data['evalue'])) {
                $where['id'] = $data['evalue'];
            } else {
                $where['evalue'] = 0;
            }
            $_reid = self::t('sysenum')->withWhere($where)->fetch();
            if ($_reid && $_reid['id'] != $data['id']) {
                $val = [];
                $val['evalue'] = $data['id'];
                $val['reid'] = $_reid['evalue'] ?: 0;
                Table::t('sysenum')->update($data['id'], $val);
            }
        }
        return 200;
    }
}
