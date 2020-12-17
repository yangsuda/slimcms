<?php

/**
 * 模板列表、表单等标签模型类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\main;

use App\Core\Forms;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Core\Page;
use SlimCMS\Error\TextException;

class TagsModel extends ModelAbstract
{

    /**
     * 数据统计
     * @param $param
     * @return array
     * @throws TextException
     */
    public static function dataCount($param): array
    {
        $param = json_decode($param, true);
        $where = [];
        if (!empty($param['ischeck'])) {
            $where['ischeck'] = $param['ischeck'] == 1 ? 1 : 2;
        }
        $param['where'] = $where;
        $res = Forms::dataCount($param);
        if ($res->getCode() != 200) {
            throw new TextException($res->getCode(), '', 'tags');
        }
        return $res->getData();
    }

    /**
     * 数据列表页
     * @param $param
     * @return array
     * @throws TextException
     */
    public static function dataList($param): array
    {
        $param = json_decode($param, true);
        $where = [];
        if (!empty($param['ischeck'])) {
            $where['ischeck'] = $param['ischeck'] == 1 ? 1 : 2;
        }
        $param['where'] = $where;
        $res = Forms::dataList($param);
        if ($res->getCode() != 200) {
            throw new TextException($res->getCode(), '', 'tags');
        }
        $data = $res->getData();
        $rangepage = aval($param, 'rangepage', 5);
        $autogoto = aval($param, 'autogoto');
        $shownum = aval($param, 'shownum');
        $data['mult'] = Page::multi($data['count'], $data['pagesize'], $data['page'], $data['currenturl'],
            $data['maxpages'], $rangepage, $autogoto, $shownum);
        return $data;
    }

    /**
     * 表单添加修改页
     * @param $param
     * @return array
     * @throws TextException
     */
    public static function dataFormHtml($param): array
    {
        $param = json_decode($param, true);
        $fid = (int)aval($param,'fid');
        $res = Forms::dataFormHtml($fid, aval($param, 'id'));
        if ($res->getCode() != 200) {
            throw new TextException($res->getCode(), '', 'tags');
        }
        return $res->getData();
    }

    /**
     * 数据详细
     * @param $param
     * @return array
     * @throws TextException
     */
    public static function dataView($param): array
    {
        $param = json_decode($param, true);
        $fid = (int)aval($param,'fid');
        $id = (int)aval($param,'id');
        $res = Forms::dataView($fid, $id);
        if ($res->getCode() != 200) {
            throw new TextException($res->getCode(), '', 'tags');
        }
        return $res->getData();
    }
}
