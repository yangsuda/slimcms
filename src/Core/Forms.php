<?php
/**
 * 表单处理类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use SlimCMS\Helper\Http;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\Str;
use SlimCMS\Interfaces\OutputInterface;

class Forms extends ModelAbstract
{
    /**
     * 某表单详细
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function formView(int $formid): OutputInterface
    {
        $form = self::t('diyforms')->withWhere($formid)->fetch();
        $data = ['form' => $form, 'formid' => $formid];
        return self::$output->withData($data);
    }

    /**
     * 生成自定义表单
     * @param string $table
     * @return OutputInterface
     */
    public static function createTable(string $table): OutputInterface
    {
        if (empty($table)) {
            return self::$output->withCode(200);
        }
        $db = self::t('forms')->db();
        $tableName = self::$setting['db']['tablepre'] . str_replace(self::$setting['db']['tablepre'], '', $table);
        if ($db->fetch("SHOW TABLES LIKE '" . $tableName . "'")) {
            return self::$output->withCode(22004, ['title' => $tableName]);
        }
        $sql = "CREATE TABLE IF NOT EXISTS `" . $tableName . "`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`ischeck` tinyint(1) NOT NULL default '2',
				`createtime` int(11) NOT NULL default '0',
				`ip` varchar(20) NOT NULL default '',
				PRIMARY KEY  (`id`)\r\n) ENGINE=MyISAM DEFAULT CHARSET=" . self::$setting['db']['dbcharset'] . "; ";
        $query = $db->query($sql);
        $db->affectedRows($query);
        return self::$output->withCode(200);
    }

    /**
     * 数据审核操作
     * @param int $formid
     * @param array $ids
     * @param int $ischeck
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataCheck(int $formid, array $ids, int $ischeck = 1): OutputInterface
    {
        if (empty($formid) || empty($ids)) {
            return self::$output->withCode(21003);
        }
        $form = self::t('forms')->withWhere($formid)->fetch();
        if (empty($form)) {
            return self::$output->withCode(22006);
        }
        $ischeck = $ischeck == 1 ? 1 : 2;
        //处理前
        if (is_callable([self::t($form['table']), 'dataCheckBefore'])) {
            $rs = self::t($form['table'])->dataCheckBefore($ids, $ischeck);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }
        self::t($form['table'])->withWhere(['id' => $ids])->update(['ischeck' => $ischeck]);
        //处理后
        if (is_callable([self::t($form['table']), 'dataCheckAfter'])) {
            $rs = self::t($form['table'])->dataCheckAfter($ids, $ischeck);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }
        $referer = (string)aval($_SERVER, 'HTTP_REFERER');
        return self::$output->withCode(200, 21032)->withReferer($referer);
    }

    /**
     * 删除数据
     * @param int $formid
     * @param array $ids
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataDel(int $formid, array $ids): OutputInterface
    {
        if (empty($formid) || empty($ids)) {
            return self::$output->withCode(21003);
        }
        $form = self::t('diyforms')->withWhere($formid)->fetch();
        if (empty($form)) {
            return self::$output->withCode(22006);
        }
        $list = self::t($form['table'])->withWhere(['id' => $ids])->fetchList();
        if (empty($list)) {
            return self::$output->withCode(21001);
        }
        foreach ($list as $k => $v) {
            if (is_callable([self::t($form['table']), 'dataDelBefore'])) {
                $rs = self::t($form['table'])->dataDelBefore($v);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }

            $form['isarchive'] == 1 && self::dataSave(7, '', ['formid' => $formid, 'aid' => $v['id'], 'content' => serialize($v)]);//归档记录

            //判断删除文章附件变量是否开启；
            if (self::$config['isDelAttachment'] == 'Y') {
                //判断属性；
                $fields = self::fieldList(['formid' => $formid, 'available' => 1, 'datatype' => ['htmltext', 'imgs', 'img', 'media', 'addon']]);
                if ($fields) {
                    Form::delAttachment(['fields' => $fields, 'data' => $v]);
                }
            }

            //删除相关数据
            if (is_callable([self::t($form['table']), 'dataDelAfter'])) {
                $rs = self::t($form['table'])->dataDelAfter($v);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }
        }
        self::t($form['table'])->withWhere(['id' => $ids])->delete();
        //获取额外数据
        if (is_callable([self::t($form['table']), 'dataDelRealAfter'])) {
            self::t($form['table'])->dataDelRealAfter($list);
        }
        $referer = Http::currentUrl() . '&p=forms/dataList&ids=';
        return self::$output->withCode(200, 21023)->withReferer($referer);
    }

    /**
     * 读取某个表单对应的详细数据
     * @param int $formid
     * @param int $id
     * @param int $cacheTime
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataView(int $formid, int $id, int $cacheTime = 300): OutputInterface
    {
        if (empty($formid) || empty($id)) {
            return self::$output->withCode(21003);
        }
        $cachekey = self::dataViewCacheKey($formid, $id);
        $data = $cacheTime > 0 ? self::$redis->get($cachekey) : [];
        if (empty($data)) {
            $form = self::t('forms')->withWhere($formid)->fetch();
            if (empty($form)) {
                return self::$output->withCode(22006);
            }
            if (is_callable([self::t($form['table']), 'dataViewBefore'])) {
                $rs = self::t($form['table'])->dataViewBefore($id);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }
            $data = self::t($form['table'])->withWhere($id)->fetch();
            if (empty($data)) {
                return self::$output->withCode(21001);
            }
            $fields = self::fieldList(['formid' => $formid, 'available' => 1]);
            Form::exchangeFieldValue($fields, $data);

            //获取额外数据
            if (is_callable([self::t($form['table']), 'dataViewAfter'])) {
                $rs = self::t($form['table'])->dataViewAfter($data);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }
            $data = ['row' => $data, 'form' => $form];
            $cacheTime && self::$redis->set($cachekey, $data, $cacheTime);
        }
        return self::$output->withData($data)->withData(200);
    }

    private static function dataViewCacheKey($formid, $id)
    {
        return __CLASS__ . __FUNCTION__ . Str::md5key(func_get_args());
    }

    /**
     * 某自定义表单下的数据统计
     * @param $param
     * @return array
     */
    public static function dataCount($param)
    {
        if (empty($param['did'])) {
            return self::result(21003);
        }
        $list = [];
        $arr = self::searchCondition($param);
        if (!empty($param['cacheTime'])) {
            $cachekey = __CLASS__ . ':' . __FUNCTION__ . ':param=' . md5(self::md5key($param) . self::md5key(aval($arr, 'data/where')));
            $list = self::$redis->get($cachekey);
        }
        if (empty($list)) {
            $form = Table::t('diyforms')->fetch($param['did']);
            if (empty($form)) {
                return self::result(22006);
            }
            if (!empty($param['joinFields'])) {
                $param['fields'] = 'main.' . str_replace(',', ',main.', $param['fields']) . ',' . $param['joinFields'];
            }

            //处理筛选条件
            $param['where'] = aval($param, 'where', []);
            $dataCountBefore = method_exists(Table::t($form['table']), 'dataCountBefore');
            $dataCountBefore && Table::t($form['table'])->dataCountBefore($param);

            $param['where'] = !empty($param['where']) ? array_merge((array)$param['where'], (array)$arr['data']['where']) : $arr['data']['where'];
            $list['count'] = Table::t($form['table'])->count($param['where']);
            $list['form'] = $form;
            $list['did'] = $param['did'];
            //缓存保存
            !empty($param['cacheTime']) && self::$redis->set($cachekey, $list, $param['cacheTime']);
        }
        return self::result(200, $list);
    }

    /**
     * 某自定义表单下的列表数据
     * @param unknown_type $row
     */
    public static function dataList($param)
    {
        if (empty($param['did'])) {
            return self::result(21003);
        }
        $form = Table::t('diyforms')->fetch($param['did']);
        if (empty($form)) {
            return self::result(22006);
        }

        if (method_exists(Table::t($form['table']), 'dataListInit')) {
            $rs = Table::t($form['table'])->dataListInit($param);
            if ($rs['code'] != 200) {
                return $rs;
            }
        }
        self::$cfg['currenturl'] = Http::currentUrl();
        $list = [];
        $arr = [];
        if (empty($param['noinput'])) {
            $arr = self::searchConditionPri($param);
        }
        if (!empty($param['cacheTime'])) {
            $cachekey = __CLASS__ . ':' . __FUNCTION__ . ':param=' . md5(self::md5key($param) . self::md5key(aval($arr, 'data/where')));
            $list = self::$redis->get($cachekey);
        }
        if (empty($list)) {
            if ($form['cpcheck'] == 1) {
                $ifcheck = aval($param, 'ifcheck');

                if ($ifcheck == '1' || $ifcheck == '2') {
                    $where = [];
                    $where['ifcheck'] = $ifcheck;
                    $param['where'] = !empty($param['where']) ? array_merge((array)$param['where'], $where) : $where;
                    empty($param['noinput']) && self::$cfg['currenturl'] .= '&ifcheck=' . $ifcheck;
                }
            }

            if (empty($param['noinput'])) {
                //根据ID筛选
                $id = aval($param, 'id');
                $id = strpos($id, '`') ? array_map('intval', explode('`', $id)) : (int)$id;
                if ($id) {
                    $where = [];
                    $where['id'] = $id;
                    $param['where'] = !empty($param['where']) ? array_merge((array)$param['where'], $where) : $where;
                    self::$cfg['currenturl'] .= '&id=' . (is_array($id) ? implode('`', $id) : $id);
                }
            }

            if (empty($param['fields'])) {
                $inlistField = defined('MANAGE') && MANAGE == 1 ? 'inlistcp' : 'inlist';
                $fields = self::fieldListPri(['formid' => $param['did'], 'available' => 1, $inlistField => 1, 'field' => 'identifier', 'onefield' => true]);
                $fields[] = 'createtime';
                $fields[] = 'ifcheck';
                $fields[] = 'operator_id';
                $fields[] = 'id';
                $param['fields'] = implode(',', $fields);
            }
            if (!empty($param['joinFields'])) {
                $param['fields'] = 'main.' . str_replace(',', ',main.', $param['fields']) . ',' . $param['joinFields'];
            }

            //处理筛选条件
            $param['where'] = aval($param, 'where', []);
            $dataListBefore = method_exists(Table::t($form['table']), 'dataListBefore');
            if ($dataListBefore) {
                $rs = Table::t($form['table'])->dataListBefore($param);
                if ($rs['code'] != 200) {
                    return $rs;
                }
            }
            if (empty($param['noinput'])) {
                $param['where'] = !empty($param['where']) ? array_merge((array)$param['where'], (array)$arr['data']['where']) : $arr['data']['where'];
            }

            //处理排序
            $param['order'] = self::validOrder($param['did'], aval($param, 'order'), aval($param, 'orderForce'));
            $list = Table::t($form['table'])->pageList($param);
            foreach ($list['infolist'] as $k => $v) {
                isset($v['ifcheck']) && $v['_ifcheck'] = $v['ifcheck'] == 1 ? '已审核' : '未审核';
                $fields = self::fieldListPri(['formid' => $param['did'], 'available' => 1]);
                Form::exchangeFieldValue($fields, $v);
                $list['infolist'][$k] = $v;
            }
            if (!empty($arr['tags'])) {
                $list['tags'] = $arr['tags'];
            }
            $list['dataListBefore'] = $dataListBefore;
            if (method_exists(Table::t($form['table']), 'dataListAfter')) {
                $rs = Table::t($form['table'])->dataListAfter($list, $param);
                if ($rs['code'] != 200) {
                    return $rs;
                }
            }
            $list['form'] = $form;
            $list['did'] = $param['did'];

            //缓存保存
            !empty($param['cacheTime']) && self::$redis->set($cachekey, $list, $param['cacheTime']);
        }
        return self::result(200, $list);
    }

    /**
     * 获取生成的表单HTML
     * @param $did
     * @param string $row
     * @return array
     */
    public static function getFormHtml($did, $row = [])
    {
        if (empty($did)) {
            return self::result(21003);
        }
        if ($row && is_numeric($row)) {
            $res = self::dataView($did, $row, 0);
            if ($res['code'] != 200) {
                return $res;
            }
            $row = $res['data']['data'];
        } else {
            $row = [];
        }

        $condition = array();
        if (is_numeric($did)) {
            $condition['formid'] = $did;
        } elseif (is_array($did)) {
            $condition = $did;
        }
        $condition['available'] = 1;
        if (CURSCRIPT != 'admincp') {
            $condition['infront'] = 1;
        }
        $form = Table::t('diyforms')->fetch($did);
        if (empty($form)) {
            return self::result(22006);
        }
        $getFormHtmlBefore = method_exists(Table::t($form['table']), 'getFormHtmlBefore');
        $getFormHtmlBefore && Table::t($form['table'])->getFormHtmlBefore($condition, $row);

        $fields = (array)self::fieldListPri($condition);
        if (empty($row)) {
            $row = [];
            foreach ($fields as $k => $v) {
                $row[$v['identifier']] = Request::input($v['identifier']);
            }
        }
        $cachekey = __CLASS__ . __FUNCTION__ . ':did=' . $did;
        $data = $did != 2 ? self::$redis->get($cachekey) : [];
        if (empty($data) || $row) {
            $iscache = empty($row);
            $row['did'] = $did;
            $fieldshtml = Form::formHtml($fields, $row);
            if (method_exists(Table::t($form['table']), 'getFormHtmlAfter')) {
                $rs = Table::t($form['table'])->getFormHtmlAfter($fieldshtml, $fields, $row);
                if ($rs['code'] != 200) {
                    return $rs;
                }
            }
            $data = ['fields' => $fields, 'fieldshtml' => $fieldshtml, 'data' => $row];
            if ($iscache) {
                self::$redis->set($cachekey, $data, 300);
            }
        }
        return self::result(200, $data);
    }

    /**
     * 保存自定义表单的数据
     * @param unknown_type $did 自定义表单对应的ID
     * @param array $row 原来的数据
     * @param array $data 要添加或修改的数据
     */
    public static function dataSave($did, $row = '', $data = [], $referer = '')
    {
        //编辑数据
        if (!empty($row) && is_numeric($row)) {
            $res = self::dataView($did, $row);
            if ($res['code'] != 200) {
                return $res;
            }
            $row = $res['data']['data'];
        }
        $res = self::requiredCheck($did, $row, $data);
        if ($res['code'] != 200) {
            return $res;
        }
        $form = Table::t('diyforms')->fetch($did);
        if (empty($form)) {
            return self::result(22006);
        }
        $data = $data ?: self::getFormValuePri($did, $row);

        //判断是否唯一
        $uniques = self::fieldListPri(['formid' => $did, 'unique' => 1, 'available' => 1]);
        foreach ($uniques as $v) {
            $exist_id = aval($data, $v['identifier']) ? Table::t($form['table'])->fetch([$v['identifier'] => $data[$v['identifier']]], 'id') : '';
            if ($exist_id && (empty($row['id']) || $exist_id != aval($row, 'id'))) {
                return self::result(22004, '', array('title' => $v['title']));
            }
        }

        if (method_exists(Table::t($form['table']), 'dataSaveBefore')) {
            $rs = Table::t($form['table'])->dataSaveBefore($data, $row);
            if ($rs['code'] != 200) {
                return $rs;
            }
        }

        if (!empty($row['id'])) {
            Table::t($form['table'])->update($row['id'], $data);
            $data['id'] = $row['id'];
            $data['mngtype'] = 'edit';
            $row = array_merge($row, $data);
            self::$redis->del(self::dataViewCacheKey($did, $row['id']));
        } else {
            $data['mid'] = !empty($data['mid']) ? $data['mid'] : self::$cfg['mid'];
            $data['operator_id'] = !empty($data['operator_id']) ? $data['operator_id'] : aval(self::$cfg, 'admin/id');
            $data['createtime'] = TIMESTAMP;
            $data['ip'] = Ipdata::getip();
            $data['id'] = Table::t($form['table'])->insert($data, true);
            $data['mngtype'] = 'add';
            $row = $data;
        }
        if (method_exists(Table::t($form['table']), 'dataSaveAfter')) {//数据插入完成之后处理对应的收尾逻辑
            $rs = Table::t($form['table'])->dataSaveAfter($data, $row);
            if ($rs['code'] != 200) {
                return $rs;
            }
        }
        $referer = $referer ?: Http::currentUrl() . '&p=diyforms/dataList&id=';
        return self::result(200, $data['id'], 21018, $referer);
    }

    /**
     * 必填检测
     * @param unknown_type $did
     */
    private static function requiredCheck($did, $row = [], $data = [])
    {
        if (empty($did)) {
            return self::result(27010);
        }
        $requireds = self::fieldListPri(['formid' => $did, 'required' => 1, 'available' => 1]);
        foreach ($requireds as $v) {
            $msg = $v['errormsg'] ? $v['errormsg'] : $v['title'];
            $val = aval($data, $v['identifier']) ?: Request::input($v['identifier']);
            if ($v['datatype'] == 'img' || $v['datatype'] == 'media' || $v['datatype'] == 'addon') {
                if (empty($row[$v['identifier']]) && empty($_FILES[$v['identifier']]['tmp_name']) && !$val) {
                    return self::result(21008, '', array('ext_msg' => $msg));
                }
            } elseif (empty($row[$v['identifier']]) && !$val) {
                return self::result(21008, '', array('ext_msg' => $msg));
            }
        }
        return self::result(200);
    }

    /**
     * 排序字段有效性检测
     * @param $formid
     * @param string $order
     * @param bool $force
     * @return string
     */
    private static function validOrder($formid, $order = '', $force = false)
    {
        if ($force === true) {
            return $order;
        }
        if (empty($order)) {
            $row = Table::t('diyforms_fields')->fetch(['formid' => $formid, 'available' => 1, 'defaultorder' => [1, 2]]);
            $order = 'main.id';
            if ($row) {
                $by = $row['defaultorder'] == 1 ? 'desc' : 'asc';
                $order = 'main.' . $row['identifier'] . ' ' . $by . ',' . $order;
            }
            return $order;
        }
        if ($order == 'rand&#040;&#041;') {
            return 'rand()';
        }
        $fields = (array)$order;
        if (strpos($order, ',')) {
            $fields = explode(',', str_replace([' desc', ' asc'], '', $order));
        }

        $valid = true;
        foreach ($fields as $v) {
            $v = trim($v);
            if ($v == 'id') {
                continue;
            }
            if (empty($v) || !Table::t('diyforms_fields')->count(['formid' => $formid, 'available' => 1, 'identifier' => $v, 'orderby' => 1])) {
                $valid = false;
                break;
            }
        }
        if ($valid === true) {
            return $order;
        }
        return 'main.id';
    }

    /**
     * 字段列表
     * @param $did
     * @param string $field
     * @param string $order
     * @return array
     */
    private static function fieldList($param)
    {
        if (empty($param)) {
            return self::result(21003);
        }
        $cachekey = __CLASS__ . __FUNCTION__ . ':param=' . Str::md5key($param);
        $list = self::$redis->get($cachekey);
        if (empty($list)) {
            $post = [];
            $post['onefield'] = aval($param, 'onefield', false);
            $post['limit'] = aval($param, 'limit');
            $post['order'] = aval($param, 'order', 'displayorder desc,id');
            $post['field'] = aval($param, 'field', '*');
            unset($param['onefield'], $param['limit'], $param['order'], $param['field']);
            $post['condition'] = $param;
            $list = Table::t('diyforms_fields')->fetchList($post);
            self::$redis->set($cachekey, $list, 60);
        }
        return self::result(200, $list);
    }

    /**
     * 获取表单提交数据
     * @param $did
     * @return array
     */
    private static function getFormValue($did, $olddata = [], $implodeUrl = false)
    {
        $fields = self::fieldListPri(['formid' => $did, 'available' => 1]);
        return Form::getFormValue($fields, $olddata, $implodeUrl);
    }

    /**
     * 生成筛选条件
     * @param $did
     * @return array
     */
    private static function searchCondition($param)
    {
        if (empty($param['did'])) {
            return self::result(21003);
        }
        $search_fields = self::fieldList(['formid' => $param['did'], 'available' => 1, 'search' => 1]);
        $data = self::getFormValue($param['did'], '', true);
        if (!empty($search_fields)) {
            foreach ($search_fields as $v) {
                if (aval($param, $v['identifier']) && !in_array($v['identifier'], ['func', 'where', 'did', 'order', 'fields', 'by', 'join', 'joinFields', 'cacheTime', 'url', 'page', 'pagesize', 'maxpages', 'autogoto', 'shownum'])) {
                    $data[$v['identifier']] = $param[$v['identifier']];
                }
            }
        }
        $data = Form::searchCondition($search_fields, $data);
        return self::result(200, $data);
    }

    /**
     * 删除图集中某张图
     * @param $did
     * @param $id
     * @param $field
     * @param $pic
     * @return array
     */
    public static function imgsDel($did, $id, $field, $pic)
    {
        if (empty($did) || empty($id) || empty($field) || empty($pic)) {
            return self::result(21003);
        }
        $res = self::dataView($did, $id);
        if ($res['code'] != 200) {
            return $res;
        }
        if (empty($res['data']['data']['_' . $field])) {
            return self::result(21001);
        }
        $pics = $res['data']['data']['_' . $field];
        $pic = str_replace(trim(self::$cfg['cfg']['basehost'], '/'), '', $pic);
        preg_match('/(.*)_([\d]+)x([\d]+).(.*)/i', $pic, $match);
        if (!empty($match)) {
            $pic = $match[1] . '.' . $match[4];
        }
        $key = md5($pic);
        if (empty($pics[$key])) {
            return self::result(21001);
        }
        unset($pics[$key]);
        Upload::uploadDel($pic);
        return self::dataSave($did, $id, [$field => serialize($pics)]);
    }

    /**
     * 联动菜单数据
     * @param $egroup
     * @return array
     */
    public static function enumsData($egroup)
    {
        $result = [];
        if ($egroup) {
            $infolist = Table::t('sysenum')->fetchList(['egroup' => $egroup], 'id,ename,evalue,reid', '', 'displayorder');
            if (!empty($infolist)) {
                foreach ($infolist as $k => $v) {
                    if (empty($v['evalue'])) {
                        unset($infolist[$k]);
                    }
                }
            }
            $result = ['status' => 1, 'infolist' => $infolist];
        }
        return self::result(200, $result);
    }

    /**
     * 导出某个个自定义表单对应的数据
     * @param type $did
     * @return type
     */
    public static function dataExport($param)
    {
        if (empty($param['did'])) {
            return self::result(21001);
        }
        $form = Table::t('diyforms')->fetch($param['did']);
        if (empty($form['table'])) {
            return self::result(22006);
        }
        $table = Table::t($form['table']);

        $row = array();
        $row['did'] = $param['did'];
        $row['page'] = aval($param, 'page', 1);
        $row['by'] = 'desc';
        $row['pagesize'] = aval($param, 'pagesize', 1000);
        $row['fields'] = '*';
        $result = self::dataList($row);
        $inlistField = defined('MANAGE') ? 'inlistcp' : 'inlist';
        $condition = ['formid' => $param['did'], 'available' => 1, $inlistField => 1];
        if (method_exists($table, 'dataExportBefore')) {
            $rs = $table->dataExportBefore($condition, $result);
            if ($rs['code'] != 200) {
                return $rs;
            }
        }
        $res = self::fieldList($condition);//处理展示字段
        $heads = array();
        $heads['id'] = ['title' => '序号', 'datatype' => ''];
        if ($form['cpcheck'] == 1) {
            $heads['ifcheck'] = ['title' => '审核状态', 'datatype' => ''];
        }
        foreach ($res['data'] as $v) {
            if (in_array($v['datatype'], array('img', 'imgs'))) {
                continue;
            }
            $heads[$v['identifier']] = $v;
        }
        $heads['createtime'] = ['title' => '创建时间', 'datatype' => 'date'];
        $result['data']['heads'] = &$heads;

        if (method_exists($table, 'dataExportAfter')) {
            $table->dataExportAfter($result['data']);
        }
        return $result;
    }
}
