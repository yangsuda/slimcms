<?php
/**
 * 表单处理类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use App\Core\Upload;
use App\Core\Ueditor;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\File;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Helper\Str;
use SlimCMS\Helper\Time;
use SlimCMS\Interfaces\OutputInterface;

class Forms extends ModelAbstract
{
    /**
     * 表单提交校验
     * @param string $formhash
     * @return OutputInterface
     */
    public static function submitCheck($formhash): OutputInterface
    {
        if (empty($formhash)) {
            return self::$output->withCode(24024);
        }
        $server = self::$request->getRequest()->getServerParams();
        $referer = '';
        if (!empty($server['HTTP_REFERER'])) {
            $parse = parse_url(aval($server, 'HTTP_REFERER'));
            $referer = $parse['host'];
        }
        $parse = parse_url(self::$config['basehost']);
        $host = $parse['host'];
        isset($_SESSION) ? '' : session_start();

        if ($server['REQUEST_METHOD'] == 'POST' &&
            $formhash == aval($_SESSION, 'formHash') &&
            empty($server['HTTP_X_FLASH_VERSION']) &&
            $host == $referer) {
            unset($_SESSION['formHash']);
            return self::$output->withCode(200);
        }
        return self::$output->withCode(24024);
    }

    /**
     * 某表单详细
     * @param int $fid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function formView($fid): OutputInterface
    {
        $form = self::t('forms')->withWhere($fid)->fetch();
        $data = ['form' => $form, 'fid' => $fid];
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
        $db = self::t()->db();
        $tableName = self::$setting['db']['tablepre'] . str_replace(self::$setting['db']['tablepre'], '', $table);
        if ($db->fetch("SHOW TABLES LIKE '" . $tableName . "'")) {
            return self::$output->withCode(22004, ['title' => $tableName]);
        }
        $sql = "CREATE TABLE IF NOT EXISTS `" . $tableName . "`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`ischeck` tinyint(1) NOT NULL default '2' COMMENT '是否审核(1=已审核，2=未审核)',
				`createtime` int(11) NOT NULL default '0' COMMENT '创建时间',
				`ip` varchar(20) NOT NULL default '' COMMENT '创建IP',
				PRIMARY KEY  (`id`)\r\n) ENGINE=MyISAM DEFAULT CHARSET=" . self::$setting['db']['dbcharset'] . "; ";
        $query = $db->query($sql);
        $db->affectedRows($query);
        return self::$output->withCode(200);
    }

    /**
     * 数据审核操作
     * @param int $fid
     * @param array $ids
     * @param int $ischeck
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataCheck(int $fid, array $ids, int $ischeck = 1): OutputInterface
    {
        if (empty($fid) || empty($ids)) {
            return self::$output->withCode(21002);
        }
        $ids = array_map('intval', $ids);
        $form = self::t('forms')->withWhere($fid)->fetch();
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
        return self::$output->withCode(200, 21032);
    }

    /**
     * 删除数据
     * @param int $fid
     * @param array $ids
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataDel(int $fid, array $ids): OutputInterface
    {
        if (empty($fid) || empty($ids)) {
            return self::$output->withCode(21002);
        }
        $ids = array_map('intval', $ids);
        $form = self::t('forms')->withWhere($fid)->fetch();
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

            $form['isarchive'] == 1 && self::dataSave(7, '', ['formid' => $fid, 'aid' => $v['id'], 'content' => serialize($v)]);//归档记录

            //判断删除文章附件变量是否开启；
            if (self::$config['isDelAttachment'] == 'Y') {
                //判断属性；
                $fields = self::fieldList(['formid' => $fid, 'available' => 1, 'datatype' => ['htmltext', 'imgs', 'img', 'media', 'addon']]);
                if ($fields) {
                    self::delAttachment($fields, $v);
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
        return self::$output->withCode(200, 21023);
    }

    /**
     * 详细数据
     * @param int $fid
     * @param int $id
     * @param int $cacheTime
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataView(int $fid, int $id, int $cacheTime = 0): OutputInterface
    {
        if (empty($fid) || empty($id)) {
            return self::$output->withCode(21002);
        }
        $cachekey = self::cacheKey('dataView', $fid, $id);
        $data = $cacheTime > 0 ? self::$redis->get($cachekey) : [];
        if (empty($data)) {
            $form = self::t('forms')->withWhere($fid)->fetch();
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
            $fields = self::fieldList(['formid' => $fid, 'available' => 1]);
            $fields && $data = self::exchangeFieldValue($fields, $data);

            //获取额外数据
            if (is_callable([self::t($form['table']), 'dataViewAfter'])) {
                $rs = self::t($form['table'])->dataViewAfter($data);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }
            $data = ['row' => $data, 'form' => $form, 'fields' => $fields];
            $cacheTime && self::$redis->set($cachekey, $data, $cacheTime);
        }
        return self::$output->withData($data)->withCode(200);
    }

    /**
     * 数据统计
     * @param array $param
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataCount(array $param): OutputInterface
    {
        if (empty($param['fid'])) {
            return self::$output->withCode(21002);
        }
        $arr = [];
        $arr['where'] = [];
        if (empty($param['noinput'])) {
            $arr = self::searchCondition($param)->getData();
        }
        if (!empty($param['cacheTime'])) {
            $cachekey = self::cacheKey(__FUNCTION__, $param, $arr['where']);
            $data = self::$redis->get($cachekey);
        }
        if (empty($data)) {
            $form = self::t('forms')->withWhere($param['fid'])->fetch();
            if (empty($form)) {
                return self::$output->withCode(22006);
            }

            if (is_callable([self::t($form['table']), 'dataCountBefore'])) {
                $rs = self::t($form['table'])->dataCountBefore($param);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }

            $where = !empty($param['where']) ? array_merge($param['where'], $arr['where']) : $arr['where'];
            $countFields = (string)aval($param, 'countFields');
            $data = [];
            $data['count'] = self::t($form['table'])->withWhere($where)->count($countFields);
            $data['form'] = $form;
            $data['fid'] = $param['fid'];
            //缓存保存
            !empty($param['cacheTime']) && self::$redis->set($cachekey, $data, $param['cacheTime']);
        }
        return self::$output->withCode(200)->withData($data);
    }

    /**
     * 列表数据
     * @param array $param
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataList(array $param): OutputInterface
    {
        if (empty($param['fid'])) {
            return self::$output->withCode(21002);
        }
        $form = self::t('forms')->withWhere($param['fid'])->fetch();
        if (empty($form)) {
            return self::$output->withCode(22006);
        }

        if (is_callable([self::t($form['table']), 'dataListInit'])) {
            $rs = self::t($form['table'])->dataListInit($param);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }

        $currenturl = self::$request->getRequest()->getUri()->getQuery();
        $param['get'] = [];
        $arr = [];
        $arr['where'] = [];
        if (empty($param['noinput'])) {
            $arr = self::searchCondition($param)->getData();
            $param['get'] = $arr['get'];
            $currenturl = $arr['currentUrl'];
        }
        if (!empty($param['cacheTime'])) {
            $cachekey = self::cacheKey(__FUNCTION__, $param, $arr['where']);
            $data = self::$redis->get($cachekey);
        }
        if (empty($data)) {
            if ($form['cpcheck'] == 1) {
                $ischeck = aval($param, 'ischeck');
                if ($ischeck) {
                    $param['get']['ischeck'] = $ischeck;
                    $where = [];
                    $where['ischeck'] = $ischeck == 1 ? 1 : 2;
                    $param['where'] = !empty($param['where']) ? array_merge((array)$param['where'], $where) : $where;
                    empty($param['noinput']) && $currenturl .= '&ischeck=' . $ischeck;
                }
            }

            if (empty($param['noinput'])) {
                //根据ID筛选
                $id = aval($param, 'id');
                $id = $id && strpos((string)$id, '`') ? array_map('intval', explode('`', $id)) : (int)$id;
                if ($id) {
                    $where = [];
                    $where['id'] = $id;
                    $param['where'] = !empty($param['where']) ? array_merge((array)$param['where'], $where) : $where;
                    empty($param['noinput']) && $currenturl .= '&id=' . (is_array($id) ? implode('`', $id) : $id);
                }
            }

            if (empty($param['fields'])) {
                $where = ['formid' => $param['fid'], 'available' => 1];
                if (isset($param['inlistField'])) {
                    $inlistField = aval($param, 'inlistField') == 'inlistcp' ? 'inlistcp' : 'inlist';
                    $where[$inlistField] = 1;
                }
                $fields = self::t('forms_fields')
                    ->withWhere($where)
                    ->onefieldList('identifier', 60);
                $fields[] = 'createtime';
                $fields[] = 'ischeck';
                $fields[] = 'id';
                $param['fields'] = implode(',', $fields);
            }
            if (!empty($param['joinFields'])) {
                $param['fields'] = 'main.' . str_replace(',', ',main.', $param['fields']) . ',' . $param['joinFields'];
            }

            if (is_callable([self::t($form['table']), 'dataListBefore'])) {
                $rs = self::t($form['table'])->dataListBefore($param);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }

            $where = !empty($param['where']) ? array_merge($param['where'], $arr['where']) : $arr['where'];
            $order = (string)aval($param, 'order');
            $orderForce = (bool)aval($param, 'orderForce');
            $order = self::validOrder($param['fid'], $order, $orderForce);
            $by = (string)aval($param, 'by', 'desc');
            $page = (int)aval($param, 'page', 1);
            $fields = (string)aval($param, 'fields');
            $pagesize = (int)aval($param, 'pagesize', 30);
            $indexField = (string)aval($param, 'indexField');
            $joins = (array)aval($param, 'joins');
            $data = self::t($form['table'])
                ->withJoin($joins)
                ->withWhere($where)
                ->withOrderby($order, $by)
                ->pageList($page, $fields, $pagesize, 0, $indexField);
            $fields = self::fieldList(['formid' => $param['fid'], 'available' => 1]);
            foreach ($data['list'] as &$v) {
                isset($v['ischeck']) && $v['_ischeck'] = $v['ischeck'] == 1 ? '已审核' : '未审核';
                $fields && $v = self::exchangeFieldValue($fields, $v);
            }
            if (!empty($arr['tags'])) {
                $data['tags'] = $arr['tags'];
            }
            $data['form'] = $form;
            $data['fid'] = $param['fid'];
            $data['by'] = $by;
            $data['currenturl'] = self::url($currenturl);
            $data['get'] = $param['get'];

            if (is_callable([self::t($form['table']), 'dataListAfter'])) {
                $rs = self::t($form['table'])->dataListAfter($data, $param);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }

            //缓存保存
            !empty($param['cacheTime']) && self::$redis->set($cachekey, $data, $param['cacheTime']);
        }
        return self::$output->withCode(200)->withData($data);
    }

    /**
     * 获取生成的表单HTML
     * @param int $fid
     * @param array $row
     * @param array $options
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function dataFormHtml(int $fid, $row = [], array $options = []): OutputInterface
    {
        if (empty($fid)) {
            return self::$output->withCode(27010);
        }
        if ($row && is_numeric($row)) {
            $res = self::dataView($fid, $row);
            if ($res->getCode() != 200) {
                return $res;
            }
            $val = $res->getData();
            $row = $val['row'];
            $form = $val['form'];
        } else {
            $row = [];
            $form = self::t('forms')->withWhere($fid)->fetch();
            if (empty($form)) {
                return self::$output->withCode(22006);
            }
        }

        $condition = [];
        $condition['formid'] = $fid;
        $condition['available'] = 1;
        if (aval($options, 'infront') === true) {
            $condition['infront'] = 1;
        }
        $fields = (array)self::fieldList($condition);
        if (empty($row)) {
            $row = [];
            foreach ($fields as $k => $v) {
                $row[$v['identifier']] = self::input($v['identifier']);
            }
        }

        if (is_callable([self::t($form['table']), 'getFormHtmlBefore'])) {
            $rs = self::t($form['table'])->getFormHtmlBefore($fields, $row, $form);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }

        $cachekey = self::cacheKey(__FUNCTION__, $fid, $options);
        $data = self::$redis->get($cachekey);
        if (empty($data) || $row) {
            $fieldshtml = self::formHtml($fid, $fields, $row, $options);

            if (is_callable([self::t($form['table']), 'getFormHtmlAfter'])) {
                $rs = self::t($form['table'])->getFormHtmlAfter($fieldshtml, $fields, $row);
                if ($rs != 200) {
                    return self::$output->withCode($rs);
                }
            }

            $data = ['fields' => $fields, 'fieldshtml' => $fieldshtml, 'data' => $row, 'form' => $form, 'fid' => $fid];
            empty($row) && !empty($options['cacheTime']) && self::$redis->set($cachekey, $data, $options['cacheTime']);
        }
        return self::$output->withCode(200)->withData($data);
    }

    /**
     * 保存自定义表单的数据
     * @param int $fid 自定义表单对应的ID
     * @param array $row 原来的数据
     * @param array $data 要添加或修改的数据
     */
    public static function dataSave(int $fid, $row = [], array $data = []): OutputInterface
    {
        //编辑数据
        if ($row && is_numeric($row)) {
            $res = self::dataView($fid, $row);
            if ($res->getCode() != 200) {
                return $res;
            }
            $val = $res->getData();
            $row = $val['row'];
            $form = $val['form'];
            $fields = $val['fields'];
        } else {
            $row = $row ?: [];
            $form = self::t('forms')->withWhere($fid)->fetch();
            if (empty($form)) {
                return self::$output->withCode(22006);
            }
            $fields = self::fieldList(['formid' => $fid, 'available' => 1]);;
        }
        $res = self::requiredCheck($fid, $row, $data);
        if ($res->getCode() != 200) {
            return $res;
        }

        $data = $data ?: self::getFormValue($fields, $row);

        //判断是否唯一
        $uniques = self::fieldList(['formid' => $fid, 'unique' => 1, 'available' => 1]);
        foreach ($uniques as $v) {
            $exist_id = aval($data, $v['identifier']) ?
                self::t($form['table'])->withWhere([$v['identifier'] => $data[$v['identifier']]])->fetch('id')
                : '';
            if ($exist_id && (empty($row['id']) || $exist_id != aval($row, 'id'))) {
                return self::$output->withCode(22004, ['title' => $v['title']]);
            }
        }

        if (is_callable([self::t($form['table']), 'dataSaveBefore'])) {
            $rs = self::t($form['table'])->dataSaveBefore($data, $row);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }

        if (!empty($row['id'])) {
            self::t($form['table'])->withWhere($row['id'])->update($data);
            $data['id'] = $row['id'];
            $data['mngtype'] = 'edit';
            $row = array_merge($row, $data);
            self::$redis->del(self::cacheKey('dataView', $fid, $row['id']));
        } else {
            $data['createtime'] = TIMESTAMP;
            $data['ip'] = Ipdata::getip();
            $data['id'] = self::t($form['table'])->insert($data, true);
            $data['mngtype'] = 'add';
            $row = $data;
        }

        if (is_callable([self::t($form['table']), 'dataSaveAfter'])) {
            $rs = self::t($form['table'])->dataSaveAfter($data, $row);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }
        return self::$output->withCode(200, 21018)->withData(['id' => $data['id']]);
    }

    /**
     * 必填检测
     * @param int $fid
     * @param array $row
     * @param array $data
     * @return OutputInterface
     */
    private static function requiredCheck(int $fid, array $row = [], array $data = []): OutputInterface
    {
        if (empty($fid)) {
            return self::$output->withCode(27010);
        }
        $requireds = self::fieldList(['formid' => $fid, 'required' => 1, 'available' => 1]);
        foreach ($requireds as $v) {
            $msg = $v['errormsg'] ? $v['errormsg'] : $v['title'];
            $val = aval($data, $v['identifier']) ?: self::input($v['identifier']);
            if ($v['datatype'] == 'img' || $v['datatype'] == 'media' || $v['datatype'] == 'addon') {
                if (empty($row[$v['identifier']]) && empty($_FILES[$v['identifier']]['tmp_name']) && !$val) {
                    return self::$output->withCode(21008, ['ext_msg' => $msg]);
                }
            } elseif (empty($row[$v['identifier']]) && !$val) {
                return self::$output->withCode(21008, ['ext_msg' => $msg]);
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 排序字段有效性检测
     * @param int $fid
     * @param string $order
     * @param bool $force
     * @return string
     * @throws \SlimCMS\Error\TextException
     */
    private static function validOrder(int $fid, string $order = '', bool $force = false): string
    {
        if ($force === true) {
            return $order;
        }
        if (empty($order)) {
            $row = self::t('forms_fields')->withWhere(['formid' => $fid, 'available' => 1, 'defaultorder' => [1, 2]])->fetch();
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
        if (strpos((string)$order, ',')) {
            $fields = explode(',', str_replace([' desc', ' asc'], '', $order));
        }

        $valid = true;
        foreach ($fields as $v) {
            $v = trim($v);
            if ($v == 'id') {
                continue;
            }
            $where = ['formid' => $fid, 'available' => 1, 'identifier' => $v, 'orderby' => 1];
            if (empty($v) || !self::t('forms_fields')->withWhere($where)->count()) {
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
     * @param string $where
     * @param string $fields
     * @param string $limit
     * @param string $order
     * @return array|bool|mixed|string|null
     * @throws \SlimCMS\Error\TextException
     */
    private static function fieldList($where = '', $fields = '*', $limit = '', $order = 'displayorder desc,id')
    {
        $cachekey = self::cacheKey(__FUNCTION__, func_get_args());
        $list = self::$redis->get($cachekey);
        if (empty($list)) {
            $list = self::t('forms_fields')
                ->withWhere($where)
                ->withLimit($limit)
                ->withOrderby($order)
                ->fetchList($fields);
            self::$redis->set($cachekey, $list, 60);
        }
        return $list;
    }

    /**
     * 生成筛选条件
     * @param array $param
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function searchCondition(array $param): OutputInterface
    {
        if (empty($param['fid'])) {
            return self::$output->withCode(21002);
        }
        $search_fields = self::fieldList(['formid' => $param['fid'], 'available' => 1, 'search' => 1]);
        $fields = self::fieldList(['formid' => $param['fid'], 'available' => 1]);
        $data = self::getFormValue($fields);

        $where = $tags = [];
        $currenturl = '';
        if (!empty($search_fields)) {
            foreach ($search_fields as $v) {
                //使模板标签支持条件筛选
                $arr = ['func', 'where', 'formid', 'order', 'fields', 'by', 'join', 'joinFields', 'cacheTime', 'url', 'page', 'pagesize', 'maxpages', 'autogoto', 'shownum'];
                if (aval($param, $v['identifier']) && !in_array($v['identifier'], $arr)) {
                    $data[$v['identifier']] = $param[$v['identifier']];
                }

                $val = aval($data, $v['identifier']);
                if (empty($v['rules']) && $val && preg_match('/,/', (string)$val)) {
                    $where[] = self::t()->field($v['identifier'], $val, 'between');
                } elseif ($v['datatype'] == 'checkbox') {
                    if (!empty($val)) {
                        foreach (explode(',', $val) as $val1) {
                            $where[] = self::t()->field($v['identifier'], $val1, 'find');
                        }
                    }
                } elseif ($v['datatype'] == 'text') {
                    if (!empty($val)) {
                        if (aval($v, 'precisesearch') == 1) {
                            $where[$v['identifier']] = $val;
                        } else {
                            $where[] = self::t()->field($v['identifier'], $val, 'like');
                        }
                    }
                } else {
                    if (!empty($val)) {
                        if (preg_match('/,/', (string)$val)) {
                            $where[$v['identifier']] = explode(',', $val);
                        } else {
                            $where[$v['identifier']] = $val;
                        }
                    }
                }

                if (!empty($v['rules'][$val])) {
                    $tags[] = [$v['identifier'], aval($v['rules'], $val)];
                } elseif (!empty($v['rules']) && !is_array($val) && preg_match('/,/', (string)$val)) {
                    $tags[] = [$v['identifier'], str_replace(',', '-', $val) . $v['units']];
                }

                if (!empty($val)) {
                    if (strpos((string)$val, ',')) {
                        if ($v['datatype'] == 'date') {
                            list($s, $e) = explode(',', $val);
                            $sdate = Time::gmdate($s);
                            $edate = Time::gmdate($e);
                            $currenturl .= '&' . $v['identifier'] . '_s' . '=' . $sdate .
                                '&' . $v['identifier'] . '_e' . '=' . $edate;
                            $data[$v['identifier'] . '_s'] = $sdate;
                            $data[$v['identifier'] . '_e'] = $edate;
                        } elseif ($v['datatype'] == 'datetime') {
                            list($s, $e) = explode(',', $val);
                            $sdate = Time::gmdate($s, 'dt');
                            $edate = Time::gmdate($e, 'dt');
                            $currenturl .= '&' . $v['identifier'] . '_s' . '=' . $sdate .
                                '&' . $v['identifier'] . '_e' . '=' . $edate;
                            $data[$v['identifier'] . '_s'] = $sdate;
                            $data[$v['identifier'] . '_e'] = $edate;
                        } else {
                            $val = str_replace(',', '`', $val);
                            $currenturl .= '&' . $v['identifier'] . '=' . $val;
                        }
                    } else {
                        $currenturl .= '&' . $v['identifier'] . '=' . $val;
                    }
                }
            }
        }
        $data = ['tags' => $tags, 'fields' => $fields, 'where' => $where, 'currentUrl' => $currenturl, 'get' => $data];
        return self::$output->withCode(200)->withData($data);
    }

    /**
     * 删除图集中某张图
     * @param int $fid
     * @param int $id
     * @param string $field
     * @param string $pic
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function imgsDel(int $fid, int $id, string $field, string $pic): OutputInterface
    {
        if (empty($fid) || empty($id) || empty($field) || empty($pic)) {
            return self::$output->withCode(21002);
        }
        $res = self::dataView($fid, $id);
        if ($res->getCode() != 200) {
            return $res;
        }
        $data = $res->getData();
        if (empty($data['row']['_' . $field])) {
            return self::$output->withCode(21001);
        }
        $pics = $data['row']['_' . $field];
        $pic = str_replace(trim(self::$config['basehost'], '/'), '', $pic);
        preg_match('/(.*)_([\d]+)x([\d]+).(.*)/i', $pic, $match);
        if (!empty($match)) {
            $pic = $match[1] . '.' . $match[4];
        }
        $key = md5($pic);
        if (empty($pics[$key])) {
            return self::$output->withCode(21001);
        }
        unset($pics[$key]);
        Upload::uploadDel($pic);
        return self::dataSave($fid, $id, [$field => serialize($pics)]);
    }

    /**
     * 联动菜单数据
     * @param $egroup
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function enumsData($egroup): OutputInterface
    {
        $result = [];
        if ($egroup) {
            $list = self::t('sysenum')->withWhere(['egroup' => $egroup])->withOrderby('displayorder')->fetchList('id,ename,evalue,reid');
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    if (empty($v['evalue'])) {
                        unset($list[$k]);
                    }
                }
            }
            $result = ['list' => $list];
        }
        return self::$output->withCode(200)->withData($result);
    }

    /**
     * 导出某个表单对应的数据
     * @param array $param
     * @return type
     */
    public static function dataExport(array $param): OutputInterface
    {
        if (empty($param['fid'])) {
            return self::$output->withCode(21001);
        }
        $form = self::t('forms')->withWhere($param['fid'])->fetch();
        if (empty($form['table'])) {
            return self::$output->withCode(22006);
        }

        $row = [];
        $row['fid'] = $param['fid'];
        $row['page'] = aval($param, 'page', 1);
        $row['by'] = 'desc';
        $row['pagesize'] = aval($param, 'pagesize', 1000);
        $row['fields'] = '*';
        $result = self::dataList($row);
        $data = $result->getData();
        foreach ($data['list'] as $k => $v) {
            $v['style'] = 'vnd.ms-excel.numberformat:@;height:30px;';
            $data['list'][$k] = $v;
        }
        $result = $result->withData($data);

        $condition = ['formid' => $param['fid'], 'available' => 1, 'isexport' => 1];
        if (is_callable([self::t($form['table']), 'dataExportBefore'])) {
            $rs = self::t($form['table'])->dataExportBefore($condition, $result);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }

        $fieldList = self::fieldList($condition);//处理展示字段
        $style = 'height:30px;font-weight:bold;background-color:#f6f6f6;text-align:center;';
        $heads = [];
        $heads['id'] = ['title' => '序号', 'datatype' => 'int', 'style' => $style];
        if ($form['cpcheck'] == 1) {
            $heads['ischeck'] = ['title' => '审核状态', 'datatype' => 'radio', 'style' => $style];
        }
        foreach ($fieldList as $v) {
            $v['style'] = $style;
            $heads[$v['identifier']] = $v;
        }
        $heads['createtime'] = ['title' => '创建时间', 'datatype' => 'date', 'style' => $style];
        $result = $result->withData(['heads' => $heads, 'form' => $form]);

        if (is_callable([self::t($form['table']), 'dataExportAfter'])) {
            $rs = self::t($form['table'])->dataExportAfter($result);
            if ($rs != 200) {
                return self::$output->withCode($rs);
            }
        }
        return call_user_func_array([get_called_class(), 'exportData'], [$result]);
    }

    /**
     * 数据导出
     * @param $param
     */
    protected static function exportData(OutputInterface $output): OutputInterface
    {
        $data = $output->getData();
        $filename = md5(serialize($data['heads'])) . '.xls';
        $dirname = 'tmpExport/';
        $tmpPath = CSDATA . $dirname;
        File::mkdir($tmpPath);
        $filepath = $tmpPath . $filename;
        $heads = &$data['heads'];

        $start = ($data['page'] - 1) * $data['pagesize'];
        $end = min($start + $data['pagesize'], $data['count']);
        $text = '总数' . $data['count'] . '条,数据处理中第' . $start . '--' . $end . '条,请稍后......';
        if ($data['page'] == 1) {
            //清除旧文件
            is_file($filepath) && unlink($filepath);

            $title = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
				<head>
			   <meta http-equiv="expires" content="Mon, 06 Jan 1999 00:00:01 GMT">
			   <meta http-equiv=Content-Type content="text/html; charset=utf-8">
			   <!--[if gte mso 9]><xml>
			   <x:ExcelWorkbook>
			   <x:ExcelWorksheets>
				 <x:ExcelWorksheet>
				 <x:Name>' . $data['form']['name'] . '</x:Name>
				 <x:WorksheetOptions>
				   <x:DisplayGridlines/>
				 </x:WorksheetOptions>
				 </x:ExcelWorksheet>
			   </x:ExcelWorksheets>
			   </x:ExcelWorkbook>
			   </xml><![endif]-->
			  </head>';
            $title .= '<table border="1" cellspacing="0" cellpadding="0"><tr>';
            foreach ($heads as $v) {
                $title .= '<td style="' . aval($v, 'style') . '">' . $v['title'] . '</td>';
            }
            $title .= "</tr>\n";
            file_put_contents($filepath, $title, FILE_APPEND);
        }
        if (!empty($data['list'])) {
            $item = '';
            foreach ($data['list'] as $info) {
                $item .= "<tr>\n";
                foreach ($heads as $k1 => $v1) {
                    if (!empty($info['_' . $k1])) {
                        if (is_array($info['_' . $k1])) {
                            $val = json_encode($info['_' . $k1]);
                        } else {
                            $val = $info['_' . $k1];
                        }
                    } else {
                        $val = aval($info, $k1);
                        if (empty($val) && in_array($v1['datatype'], ['date', 'datetime'])) {
                            $val = '';
                        }
                    }

                    $item .= "<td style='" . aval($info, 'style') . "'>" . $val . "</td>";
                }
                $item .= "</tr>";
            }
        }
        $down = '';
        if ($data['page'] + 1 >= $data['maxpages']) {
            $down = '&down=1';
        }
        if ($data['page'] >= $data['maxpages']) {
            $item .= '</table>';
        }
        file_put_contents($filepath, $item, FILE_APPEND);
        $data['page']++;
        $url = self::url('&page=' . $data['page'] . $down);
        return self::$output->withData(['file' => $filepath, 'text' => $text])->withReferer($url);
    }

    /**
     * 对数据库查询数据进行转换处理
     * @param array $fields
     * @param array $v
     * @return array
     * @throws \SlimCMS\Error\TextException
     */
    private static function exchangeFieldValue(array $fields, array $v): array
    {
        if (empty($fields)) {
            return [];
        }
        isset($v['createtime']) && $v['_createtime'] = $v['createtime'] ? Time::gmdate($v['createtime'], 'dt') : '';
        foreach ($fields as $val) {
            $identifier = &$val['identifier'];
            if (!isset($v[$identifier])) {
                continue;
            }
            $v[$identifier . '_units'] = $val['units'];
            switch ($val['datatype']) {
                case 'htmltext':
                    $v['_' . $identifier] = stripslashes($v[$identifier]);
                    break;
                case 'price':
                    $v['_' . $identifier] = $v[$identifier] ? round($v[$identifier], 2) : '';
                    break;
                case 'date':
                    $v['_' . $identifier] = $v[$identifier] ? Time::gmdate($v[$identifier]) : '';
                    break;
                case 'datetime':
                    $v['_' . $identifier] = $v[$identifier] ? Time::gmdate($v[$identifier], 'dt') : '';
                    break;
                case 'float':
                    $v['_' . $identifier] = $v[$identifier] ? round($v[$identifier], 4) : '';
                    break;
                case 'checkbox':
                    if (!empty($v[$identifier])) {
                        $rules = unserialize($val['rules']);
                        $arr = [];
                        foreach (explode(',', $v[$identifier]) as $_v) {
                            if (!empty($_v)) {
                                $arr[] = aval($rules, $_v);
                            }
                        }
                        $v['_' . $identifier] = implode('、', $arr);
                    }
                    break;
                case 'stepselect':
                    $v['_' . $identifier] = aval($v, $identifier) ? self::t('sysenum')
                        ->withWhere(['egroup' => $val['egroup'], 'evalue' => $v[$identifier]])
                        ->fetch('ename') : '';
                    break;
                case 'select':
                case 'radio':
                    $rules = unserialize($val['rules']);
                    $v['_' . $identifier] = aval($rules, $v[$identifier]);
                    break;
                case 'img':
                    $width = aval(self::$config, 'picWidth', 800);
                    $height = aval(self::$config, 'picHeight', 800);
                    $v['_' . $identifier] = copyImage($v[$identifier], $width, $height);
                    break;
                case 'imgs':
                    $width = aval(self::$config, 'picWidth', 800);
                    $height = aval(self::$config, 'picHeight', 800);
                    $img = unserialize($v[$identifier]);
                    if (is_array($img)) {
                        foreach ($img as $k1 => $v1) {
                            $v1['originalImg'] = $v1['img'];
                            $v1['img'] = copyImage($v1['img'], $width, $height);
                            $img[$k1] = $v1;
                        }
                    }
                    $v['_' . $identifier] = !empty($v[$identifier]) ? $img : [];
                    break;
                case 'media':
                case 'addon':
                    $v['_' . $identifier] = $v[$identifier] ? trim(self::$config['basehost'], '/') . $v[$identifier] : '';
                    break;
                case 'serialize':
                    $v['_' . $identifier] = unserialize($v[$identifier]);
                    break;
                default:
                    $v['_' . $identifier] = self::$request->htmlspecialchars($v[$identifier], 'de');
                    break;
            }
        }
        return $v;
    }

    /**
     * 获取表单提交数据
     * @param array $fields
     * @param array $olddata
     * @return array
     * @throws \SlimCMS\Error\TextException
     */
    private static function getFormValue(array $fields, array $olddata = []): array
    {
        $cfg = &self::$config;
        $data = [];
        foreach ($fields as $k => $v) {
            if (!empty($olddata['id']) && ($v['datatype'] == 'readonly' || $v['forbidedit'] == 2)) {
                continue;
            }
            $identifier = &$v['identifier'];
            if (!empty($v['rules'])) {
                $v['rules'] = unserialize($v['rules']);
                $val = self::input($identifier);
                if (isset($val)) {
                    if (is_array($val)) {
                        $vals = $val;
                    } else {
                        $vals = $val || $val == '0' ? explode('`', $val) : [];
                    }
                    foreach ($vals as $val) {
                        if (array_key_exists($val, $v['rules'])) {
                            $data[$identifier][] = $val;
                        }
                    }
                    $data[$identifier] = !empty($data[$identifier]) ? implode(',', $data[$identifier]) : '';
                }
                if ($v['datatype'] == 'checkbox') {
                    $data[$identifier] = !empty($data[$identifier]) ? $data[$identifier] : '';
                }
            } else {
                switch ($v['datatype']) {
                    case 'htmltext':
                        $val = (string)self::input($identifier, 'htmltext');
                        if (isset($val)) {
                            $data[$identifier] = Str::filterHtml($val);
                        }
                        break;
                    case 'int':
                        $val = self::input($identifier);
                        if ($val && strpos((string)$val, '`')) {
                            $arr = explode('`', $val);
                            $val = array_map('intval', $arr);
                            $data[$identifier] = implode(',', $val);
                        } else {
                            $val = self::input($identifier, 'int');
                            if (isset($val)) {
                                $data[$identifier] = $val;
                            }
                        }
                        break;
                    case 'stepselect':
                        $val = self::input($v['egroup'], 'int');
                        if (isset($val)) {
                            $data[$identifier] = $val;
                        }
                        break;
                    case 'float':
                    case 'tel':
                    case 'price':
                        $val = self::input($identifier, $v['datatype']);
                        if (isset($val)) {
                            $data[$identifier] = $val;
                        }
                        break;
                    case 'date':
                    case 'datetime':
                        $vals = self::input($identifier . '_s');
                        $vale = self::input($identifier . '_e');
                        if ($vals && $vale) {
                            $data[$identifier] = strtotime($vals) . ',' . strtotime($vale);
                        } else {
                            $val = self::input($identifier);
                            if (isset($val)) {
                                $data[$identifier] = strtotime($val);
                            }
                        }
                        break;
                    case 'imgs':
                        $imgurls = array();
                        if (!empty($olddata[$identifier])) {
                            $imgurls = unserialize($olddata[$identifier]);
                            foreach ($imgurls as $_k => $_v) {
                                $_v['text'] = str_replace("'", "`", self::input('imgmsg' . $_k));
                                $imgurls[$_k] = $_v;
                            }
                        }
                        if ($cfg['clienttype'] > 0) {
                            for ($i = 0; $i < 10; $i++) {
                                $picUrl = self::input($identifier . '_' . $i, 'img');
                                if ($picUrl) {
                                    $key = md5($picUrl);
                                    $imgurls[$key]['img'] = $picUrl;
                                    $imgurls[$key]['text'] = '';
                                    $imginfos = getimagesize(CSPUBLIC . $imgurls[$key]['img']);
                                    $imgurls[$key]['width'] = $imginfos[0];
                                    $imgurls[$key]['height'] = $imginfos[1];
                                }
                            }
                        } else {
                            isset($_SESSION) ? '' : @session_start();
                            $res = Upload::getWebupload();
                            if ($res->getCode() == 200) {
                                $imgurls += (array)$res->getData();
                            }
                        }
                        $data[$identifier] = $imgurls ? serialize($imgurls) : '';
                        break;
                    case 'img':
                    case 'media':
                    case 'addon':
                        $val = self::input($identifier);
                        $rule = '';
                        if (!empty($cfg['whitePicUrl'])) {
                            $func = function ($val) {
                                return str_replace('/', '\/', trim($val));
                            };
                            $rule = '^' . implode('|^', array_map($func, explode("\n", $cfg['whitePicUrl'])));
                        }
                        if ($val && $rule && preg_match('/' . $rule . '/', (string)$val)) {
                            $data[$identifier] = $val;
                        } else {
                            $data[$identifier] = self::input($identifier, $v['datatype']);
                            if (!empty($olddata[$identifier])) {
                                if (empty($data[$identifier])) {
                                    unset($data[$identifier]);
                                } else {
                                    Upload::uploadDel($olddata[$identifier]);
                                }
                            }
                        }
                        break;
                    case 'serialize':
                        $val = self::input($identifier);
                        $data[$identifier] = is_array($val) ? serialize($val) : self::$request->htmlspecialchars($val, 'de');
                        break;
                    default:
                        $val = self::input($identifier);
                        if (isset($val)) {
                            $data[$identifier] = $val;
                        }
                        break;
                }
            }
        }

        return $data;
    }

    /**
     * 删除附件
     * @param array $fields 字段列表
     * @param array $data 某条数据
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function delAttachment(array $fields, array $data): OutputInterface
    {
        if (empty($fields) || empty($data)) {
            return self::$output->withCode(21002);
        }
        foreach ($fields as $v) {
            if (empty($data[$v['identifier']])) {
                continue;
            }
            switch ($v['datatype']) {
                case 'htmltext':
                    //取出文章附件；
                    $pattern = '/(\\' . rtrim(self::$setting['attachment']['dirname'], '/') . '.+?)(\"|\|| )/';
                    preg_match_all($pattern, stripslashes($data[$v['identifier']]) . ' ', $delname);
                    //移出重复附件；
                    $delname = array_unique($delname['1']);
                    foreach ($delname as $var) {
                        Upload::uploadDel($var);
                    }
                    break;
                case 'imgs':
                    foreach (unserialize($data[$v['identifier']]) as $p) {
                        Upload::uploadDel($p['img']);
                    }
                    break;
                case 'media':
                case 'addon':
                case 'img':
                    Upload::uploadDel($data[$v['identifier']]);
                    break;
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 获取表单提交所需要的信息
     * @param int $fid
     * @param array $fields
     * @param array $row
     * @param array $options
     * @return array
     * @throws \SlimCMS\Error\TextException
     */
    private static function formHtml($fid, array $fields, array $row = [], array $options = []): array
    {
        foreach ($fields as $k => $v) {
            $v['maxlength'] = $maxlength = !empty($v['maxlength']) ? 'maxlength="' . $v['maxlength'] . '"' : '';
            $v['rules'] = !empty($v['rules']) ? unserialize($v['rules']) : array();
            $v['default'] = !empty($row[$v['identifier']]) ? $row[$v['identifier']] : html_entity_decode((string)$v['default']);

            //Validform规则设置
            $v['checkrule'] = (empty($v['checkrule']) && $v['required'] == 1) ? '*' : $v['checkrule'];
            if ($v['required'] == 1) {
                $text = in_array($v['datatype'], ['select', 'radio', 'checkbox']) ? '请选择' : ($v['datatype'] == 'img' ? '请上传' : '请输入');
                empty($v['nullmsg']) && $v['nullmsg'] = $text . $v['title'];
                empty($v['tip']) && $v['tip'] = $text . $v['title'];
            }
            $datatype = !empty($v['checkrule']) ? 'datatype="' . $v['checkrule'] . '" ' : '';
            if (empty($v['intro']) && $datatype) {
                $v['intro'] = in_array($v['datatype'], array('select', 'radio', 'checkbox')) ? '必选' : '';
            }
            $nullmsg = !empty($v['nullmsg']) ? 'nullmsg="' . $v['nullmsg'] . '" ' : '';
            $ignore = empty($v['required']) ? 'ignore="ignore" ' : '';
            $tip = !empty($v['tip']) ? 'placeholder="' . $v['tip'] . '" ' : '';
            $errormsg = !empty($v['errormsg']) ? 'errormsg="' . $v['errormsg'] . '" ' : '';
            $readonly = !empty($row['id']) && $v['forbidedit'] == 2 ? ' readonly' : '';
            $v['validform'] = $validform = ' sucmsg="" ' . $datatype . $nullmsg . $tip . $errormsg . $ignore . $readonly;

            $template = 'block/fieldshtml/' . $v['datatype'];
            switch ($v['datatype']) {
                case 'float':
                case 'price':
                case 'tel':
                case 'int':
                case 'text':
                case 'select':
                case 'radio':
                case 'checkbox':
                case 'multitext':
                case 'media':
                case 'hidden':
                case 'readonly':
                case 'addon':
                    $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    break;
                case 'htmltext':
                    if (self::$config['clienttype'] > 0) {
                        //换行转换处理
                        $v['default'] = str_replace(array('&lt;br /&gt;', '&lt;br&gt;'), "\n", $v['default']);
                        $v['default'] = stripslashes($v['default']);
                        $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    } else {
                        $v['default'] = stripslashes($v['default']);
                        $config = ['identity' => aval($options, 'ueditorType', 'small')];
                        $res = Ueditor::ueditor($v['identifier'], $v['default'], $config)->getData();
                        $v['field'] = $res['ueditor'];
                    }
                    break;
                case 'stepselect':
                    static $loadonce = 0;
                    $loadonce++;
                    $v['loadonce'] = $loadonce;
                    $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    break;
                case 'date':
                case 'datetime':
                    $type = $v['datatype'] == 'date' ? 'd' : 'dt';
                    if (!empty($row[$v['identifier']])) {
                        $v['default'] = Time::gmdate($row[$v['identifier']], $type);
                    } else {
                        $v['default'] = !empty($v['default']) ?
                            Time::gmdate((TIMESTAMP + $v['default'] * 86400), $type) :
                            (empty($row) ? Time::gmdate(TIMESTAMP, $type) : '');
                    }
                    static $isLoadDatetimepicker = 0;
                    $isLoadDatetimepicker++;
                    $v['isLoadDatetimepicker'] = $isLoadDatetimepicker;
                    $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    break;
                case 'multidate':
                    static $isLoadMultidate = 0;
                    $isLoadMultidate++;
                    $v['isLoadMultidate'] = $isLoadMultidate;
                    $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    break;
                case 'imgs':
                    $v['imgs'] = !empty($v['default']) ? unserialize($v['default']) : [];
                    $v['fid'] = $fid;
                    $v['row'] = $row;

                    //清除session里的图片信息
                    isset($_SESSION) ? '' : session_start();
                    if (!empty($_SESSION['bigfile_info']) && is_array($_SESSION['bigfile_info'])) {
                        foreach ($_SESSION['bigfile_info'] as $s_v) {
                            Upload::uploadDel($s_v);
                        }
                    }
                    $_SESSION['bigfile_info'] = [];
                    $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    break;
                case 'img':
                    static $isLoadh5upload = 0;
                    $isLoadh5upload++;
                    $v['isLoadh5upload'] = $isLoadh5upload;
                    $v['fid'] = $fid;
                    $v['row'] = $row;
                    $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    break;
                case 'serialize':
                    $val = var_export(unserialize($v['default']), true);
                    $v['val'] = nl2br(str_replace(["array (\n", "),\n", ")"], '', $val));
                    $v['field'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                    break;
            }
            $fields[$k] = $v;
        }
        return $fields;
    }

    /**
     * 后台列表展示字段
     * @param $fid
     * @param int $limit
     * @return OutputInterface
     */
    public static function listFields(int $fid, $limit = 15, $fieldName = 'inlistcp'): OutputInterface
    {
        if (empty($fid)) {
            return self::$output->withCode(27010);
        }
        $listFields = self::fieldList(['formid' => $fid, 'available' => 1, $fieldName => 1], '*', $limit);
        return self::$output->withCode(200)->withData(['listFields' => $listFields]);
    }

    /**
     * 参与搜索字段
     * @param $fid
     * @return OutputInterface
     */
    public static function searchFields(int $fid): OutputInterface
    {
        if (empty($fid)) {
            return self::$output->withCode(27010);
        }
        $searchFields = self::fieldList(['formid' => $fid, 'available' => 1, 'search' => 1]);
        if (!empty($searchFields)) {
            foreach ($searchFields as &$v) {
                if ($v['datatype'] == 'stepselect') {
                    $v['default'] = self::input($v['egroup'], 'int');
                    static $loadonce = 0;
                    $loadonce++;
                    $v['loadonce'] = $loadonce;
                    $template = 'block/fieldshtml/' . $v['datatype'];
                    $v['fieldHtml'] = self::$output->withData($v)->withTemplate($template)->analysisTemplate(true);
                }
            }
        }
        return self::$output->withCode(200)->withData(['searchFields' => $searchFields]);
    }

    /**
     * 参与排序字段
     * @param $fid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function orderFields(int $fid): OutputInterface
    {
        if (empty($fid)) {
            return self::$output->withCode(27010);
        }
        $where = ['formid' => $fid, 'available' => 1, 'orderby' => 1];
        $data = [];
        $data['orderFields'] = self::t('forms_fields')->withWhere($where)->onefieldList('id');
        return self::$output->withCode(200)->withData($data);
    }
}
