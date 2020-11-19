<?php

/**
 * 模板列表、表单等标签模型类
 * @author zhucy
 * @date 2020.05.13
 */

namespace app\model\main;

use app\model\admincp\ActivityModel;
use app\model\diyforms\DiyformsModel;
use cs090\core\Model;
use cs090\core\Output;

class TagsModel extends Model
{
    /**
     * 数据统计
     * @param $param
     * @return mixed
     */
    public static function dataCount($param)
    {
        $param = json_decode($param, true);
        $param['fid'] = $param['fid'];
        $where = [];
        if (!empty($param['ischeck'])) {
            $where['ischeck'] = $param['ischeck']==1 ? 1 : 2;
        } else {
            $where['ischeck'] = [1, 2];
        }
        $param['where'] = $where;
        $res = DiyformsModel::dataCount($param);
        if ($res['code'] != 200) {
            Output::showMsg($res);
        }
        return $res['data'];
    }

    /**
     * 数据列表页
     * @return array|\cs090\core\数据|string
     */
    public static function dataList($param)
    {
        $param = json_decode($param, true);
        $param['fid'] = $param['fid'];
        $where = [];
        if (!empty($param['ischeck'])) {
            $where['ischeck'] = $param['ischeck']==1 ? 1 : 2;
        } else {
            $where['ischeck'] = [1, 2];
        }
        $param['where'] = $where;
        $res = DiyformsModel::dataList($param);
        if ($res['code'] != 200) {
            Output::showMsg($res);
        }
        $result = DiyformsModel::fieldList(['formid' => $param['fid'], 'available' => 1, 'inlist' => 1]);//处理展示字段
        $res['data']['listFields'] = $result['data'];
        return $res['data'];
    }

    /**
     * 自定义表单添加修改页
     * @return array|\cs090\core\数据|string
     */
    public static function dataForm($param)
    {
        $param = json_decode($param, true);
        $res = DiyformsModel::diyformView($param['fid']);
        $result = DiyformsModel::getFormHtml($param['fid'], aval($param, 'id'));
        if ($result['code'] != 200) {
            Output::showMsg($result);
        }
        $res['data'] += $result['data'];
        return $res['data'];
    }
}
