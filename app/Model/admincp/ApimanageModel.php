<?php

/**
 * 接口管理模型类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\admincp;

use App\Core\Forms;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\Time;
use SlimCMS\Interfaces\OutputInterface;

class ApimanageModel extends ModelAbstract
{

    /**
     * 根据表单设置生成或删除相应接口
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function formApiManage(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }

        $rules = self::t('forms_fields')->withWhere(5)->fetch('rules');
        $rules = unserialize($rules);
        foreach ($rules as $k => $v) {
            $func = 'apiSave' . $k;
            self::$func($formid);
        }
        return self::$output->withCode(200);
    }

    /**
     * 获取参数类型
     * @param $datatype
     * @param string $fieldtype
     * @return mixed
     */
    private static function paramType($datatype, $fieldtype = '')
    {
        //参数类型
        $datatypes = [
            'text' => '1',
            'multitext' => '1',
            'htmltext' => '1',
            'int' => '2',
            'float' => '4',
            'select' => '2',
            'radio' => '2',
            'checkbox' => '1',
            'stepselect' => '2',
            'date' => '5',
            'multidate' => '1',
            'datetime' => '5',
            'imgs' => '1',
            'img' => '6',
            'media' => '6',
            'addon' => '6',
            'tel' => '7',
            'hidden' => '1',
            'price' => '8',
            'readonly' => '1',
            'serialize' => '1',
            'password' => '1',
        ];

        //参数类型
        $fieldtypes = [
            'varchar' => '1',
            'text' => '1',
            'int' => '2',
            'tinyint' => '2',
            'smallint' => '2',
            'mediumint' => '2',
            'double' => '4',
            'float' => '4',
            'decimal' => '8',
            'bigint' => '2',
            'char' => '1',
            'mediumtext' => '1',
            'longtext' => '1',
            'enum' => '1',
            'set' => '6',
        ];
        if (!empty($fieldtype)) {
            return $fieldtypes[$fieldtype];
        }
        return $datatypes[$datatype];
    }

    /**
     * 返回数据类型
     * @param $datatype
     * @return mixed
     */
    private static function responseDataType($datatype, $types = 1)
    {
        //参数类型
        $datatypes1 = [
            'text' => '1',
            'multitext' => '1',
            'htmltext' => '1',
            'int' => '2',
            'float' => '4',
            'select' => '1',
            'radio' => '1',
            'checkbox' => '1',
            'stepselect' => '2',
            'date' => '2',
            'multidate' => '1',
            'datetime' => '2',
            'imgs' => '1',
            'img' => '6',
            'media' => '6',
            'addon' => '6',
            'tel' => '7',
            'hidden' => '1',
            'price' => '8',
            'readonly' => '1',
            'serialize' => '1',
            'password' => '1',
        ];
        $datatypes2 = [
            'text' => '1',
            'multitext' => '1',
            'htmltext' => '1',
            'int' => '2',
            'float' => '4',
            'select' => '1',
            'radio' => '1',
            'checkbox' => '1',
            'stepselect' => '2',
            'date' => '5',
            'multidate' => '1',
            'datetime' => '5',
            'imgs' => '3',
            'img' => '6',
            'media' => '6',
            'addon' => '6',
            'tel' => '7',
            'hidden' => '1',
            'price' => '8',
            'readonly' => '1',
            'serialize' => '1',
            'password' => '1',
        ];
        if ($types == 1) {
            return $datatypes1[$datatype];
        }
        if ($types == 2) {
            return $datatypes2[$datatype];
        }
    }

    /**
     * 生成demo数据
     * @param $datatype
     * @param int $types
     * @return mixed
     */
    private static function responseDataDemoType($datatype, $types = 1)
    {
        $path = self::$config['basehost'] . '/uploads/2021/09/';
        //参数类型
        $datatypes1 = [
            'text' => '测试数据',
            'multitext' => '测试数据',
            'htmltext' => self::$request->htmlspecialchars('<p style="text-align:center">测试数据</p>'),
            'int' => '2',
            'float' => '2.3',
            'select' => '1',
            'radio' => '1',
            'checkbox' => '1,2,3',
            'stepselect' => '2',
            'date' => TIMESTAMP,
            'multidate' => date('Y-m') . '-10,' . date('Y-m') . '-11',
            'datetime' => TIMESTAMP,
            'imgs' => 'a:2:{s:32:"3dbc36f4004be02d8d9c6c00e62ec04a";a:5:{s:3:"img";s:79:"' . $path . '497b3f3fd9ba6152d4b4a5af4400487159.jpg";s:4:"text";s:0:"";s:5:"width";i:525;s:6:"height";i:240;s:11:"originalImg";s:55:"/uploads/2021/09/497b3f3fd9ba6152d4b4a5af4400487159.jpg";}s:32:"7712bd6191d3e078a26c0b936e44a84b";a:5:{s:3:"img";s:79:"' . $path . '497b3f3fd9ba6152d4b4aff06272732296.jpg";s:4:"text";s:0:"";s:5:"width";i:80;s:6:"height";i:80;s:11:"originalImg";s:55:"/uploads/2021/09/497b3f3fd9ba6152d4b4aff06272732296.jpg";}}',
            'img' => '/uploads/2021/09/497b3f3fd9ba6152d4b4a5af4400487159.jpg',
            'media' => '/uploads/2021/09/1921681125fc9bd7622814989568951.mp3',
            'addon' => '/uploads/2021/09/1921681125fdb23dfa13ec614899116.rar',
            'tel' => '13962311111',
            'hidden' => '测试数据',
            'price' => '9.8',
            'readonly' => '测试数据',
            'serialize' => '',
            'password' => '',
        ];
        $datatypes2 = [
            'text' => '测试数据',
            'multitext' => '测试数据',
            'htmltext' => self::$request->htmlspecialchars('<p style="text-align:center">测试数据</p>'),
            'int' => '2',
            'float' => '2.3',
            'select' => '下拉选项',
            'radio' => '单选项',
            'checkbox' => '张三、李四、王五',
            'stepselect' => '测试分类',
            'date' => Time::gmdate(TIMESTAMP),
            'multidate' => date('Y-m') . '-10,' . date('Y-m') . '-11',
            'datetime' => Time::gmdate(TIMESTAMP, 'dt'),
            'imgs' => '{"3dbc36f4004be02d8d9c6c00e62ec04a": {"img": "' . $path . '497b3f3fd9ba6152d4b4a5af4400487159.jpg","text": "","width": 525,"height": 240,"originalImg": "' . $path . '497b3f3fd9ba6152d4b4a5af4400487159.jpg"}, "7712bd6191d3e078a26c0b936e44a84b": {"img": "' . $path . '497b3f3fd9ba6152d4b4aff06272732296.jpg","text": "","width": 80,"height": 80,"originalImg": "' . $path . '497b3f3fd9ba6152d4b4aff06272732296.jpg"}}',
            'img' => $path . '497b3f3fd9ba6152d4b4a5af4400487159.jpg',
            'media' => $path . '1921681125fc9bd7622814989568951.mp3',
            'addon' => $path . '1921681125fdb23dfa13ec614899116.rar',
            'tel' => '13962311111',
            'hidden' => '测试数据',
            'price' => '9.8',
            'readonly' => '测试数据',
            'serialize' => '',
            'password' => '',
        ];
        if ($types == 1) {
            return $datatypes1[$datatype];
        }
        if ($types == 2) {
            return $datatypes2[$datatype];
        }
    }

    /**
     * 表单列表接口生成
     * @param $formid
     * @param $apiid
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave1(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '列表';
            $data['path'] = 'api/dataList';
            $data['method'] = 2;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            //是否审核参数
            if ($group['cpcheck'] == 1) {
                $val = [];
                $val['name'] = 'ischeck';
                $val['types'] = 2;
                $val['apiid'] = $apiid;
                $val['intro'] = '是否审核，2未审核，1已审核，默认所有';
                self::apiRequestSave($val);
            }

            $data = [
                [
                    'name' => 'fid',
                    'types' => 2,
                    'intro' => '固定值：' . $formid,
                    'ismust' => 1,
                ],
                [
                    'name' => 'page',
                    'types' => 2,
                    'intro' => '翻页',
                    'default' => '1',
                ],
                [
                    'name' => 'pagesize',
                    'types' => 2,
                    'intro' => '一页显示数量',
                    'default' => '30',
                ],
                [
                    'name' => 'by',
                    'types' => 1,
                    'intro' => '排序（desc倒序，asc正序）',
                    'default' => 'desc',
                ],
            ];
            foreach ($data as $v) {
                $val = [];
                $val['name'] = $v['name'];
                $val['types'] = $v['types'];
                $val['apiid'] = $apiid;
                $val['intro'] = $v['intro'];
                $val['ismust'] = aval($v, 'ismust');
                $val['default'] = aval($v, 'default');
                self::apiRequestSave($val);
            }

            //排序参数
            $orderFields = Forms::fieldList(['formid' => $formid, 'available' => 1, 'orderby' => 1]);
            if ($orderFields) {
                $data = [];
                $data['name'] = 'order';
                $data['types'] = 1;
                $data['apiid'] = $apiid;
                $data['intro'] = '排序(';
                foreach ($orderFields as $v) {
                    $data['intro'] .= $v['identifier'] . ':' . $v['title'] . ',';
                }
                $data['intro'] .= ',rand()随机显示)';
                self::apiRequestSave($data);
            }

            $result1 = Forms::searchFields($formid);//搜索条件显示
            $searchFields = $result1->getData()['searchFields'];
            foreach ($searchFields as $v) {
                if (in_array($v['datatype'], ['date', 'datetime'])) {
                    $data = [];
                    $data['name'] = $v['identifier'] . '_s';
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(开始时间，格式：' . date('Y-m-d', TIMESTAMP) . ')';
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);

                    $data = [];
                    $data['name'] = $v['identifier'] . '_e';
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(截止时间，格式：' . date('Y-m-d', TIMESTAMP) . ')';
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                } elseif (in_array($v['datatype'], ['checkbox'])) {
                    $data = [];
                    $data['name'] = $v['identifier'];
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(多个筛选用“`”隔开，如:1`2)(';
                    foreach (unserialize($v['rules']) as $k1 => $v1) {
                        $data['intro'] .= $k1 . ':' . $v1 . ',';
                    }
                    $data['intro'] .= ')';
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                } else {
                    $data = [];
                    $data['name'] = $v['identifier'];
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['default'] = '';
                    $data['intro'] = $v['title'];
                    if (!empty($v['rules'])) {
                        foreach (unserialize($v['rules']) as $k1 => $v1) {
                            $data['intro'] .= '(' . $k1 . ':' . $v1 . ',)';
                        }
                    }
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                }
            }

            //添加返回参数
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'page', 'types' => 2, 'intro' => '当前页数']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'pagesize', 'types' => 2, 'intro' => '一页显示数量']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'count', 'types' => 2, 'intro' => '总数']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'maxpages', 'types' => 2, 'intro' => '总页数']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'list.id', 'types' => 2, 'intro' => '自增ID']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'list.createtime', 'types' => 2, 'intro' => '创建时间(时间戳)']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'list._createtime', 'types' => 5, 'intro' => '创建时间(日期时间格式)']);

            //开启后台审核
            if ($group['cpcheck'] == 1) {
                self::apiResponseSave(['apiid' => $apiid, 'name' => 'list.ischeck', 'types' => 2, 'intro' => '审核状态(1已审核，2未审核)']);
                self::apiResponseSave(['apiid' => $apiid, 'name' => 'list._ischeck', 'types' => 1, 'intro' => '审核状态对应文字']);
            }

            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';
            $dataDemo['data']['count'] = '1';
            $dataDemo['data']['maxpages'] = '3';
            $dataDemo['data']['page'] = '1';
            $dataDemo['data']['pagesize'] = '1';

            //处理展示字段
            $result = Forms::listFields($formid, 500, 'inlist');
            $listFields = $result->getData()['listFields'];
            $arr = [];
            foreach ($listFields as $v) {
                $intro = $v['title'];
                if (!empty($v['rules'])) {
                    $intro .= '(';
                    foreach (unserialize($v['rules']) as $k1 => $v1) {
                        $intro .= $k1 . ':' . $v1 . ',';
                    }
                    $intro .= ')';
                }

                self::apiResponseSave([
                    'apiid' => $apiid,
                    'fieldid' => $v['id'],
                    'name' => 'list.' . $v['identifier'],
                    'types' => self::responseDataType($v['datatype'], 1),
                    'intro' => $intro
                ]);
                self::apiResponseSave([
                    'apiid' => $apiid,
                    'fieldid' => $v['id'],
                    'name' => 'list._' . $v['identifier'],
                    'types' => self::responseDataType($v['datatype'], 2),
                    'intro' => $v['title'] . '对应文字'
                ]);

                if ($group['cpcheck'] == 1) {
                    $arr['ischeck'] = 1;
                    $arr['_ischeck'] = '已审核';
                }

                $arr[$v['identifier']] = self::responseDataDemoType($v['datatype'], 1);
                $arr['_' . $v['identifier']] = self::responseDataDemoType($v['datatype'], 2);
                if (!empty($v['units'])) {
                    self::apiResponseSave([
                        'apiid' => $apiid,
                        'fieldid' => $v['id'],
                        'name' => 'list.' . $v['identifier'] . '_units',
                        'types' => 1,
                        'intro' => $v['title'] . '对应度量单位'
                    ]);
                    $arr[$v['identifier'] . '_units'] = $v['units'];
                }
            }
            $dataDemo['data']['list'][] = $arr;
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 表单详细接口生成
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave2(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '详细';
            $data['path'] = 'api/dataView';
            $data['method'] = 2;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            $data = [
                [
                    'name' => 'fid',
                    'types' => 2,
                    'intro' => '固定值：' . $formid,
                    'ismust' => 1,
                ],
                [
                    'name' => 'id',
                    'types' => 2,
                    'intro' => '信息ID',
                    'ismust' => 1,
                ],
            ];
            foreach ($data as $v) {
                $val = [];
                $val['name'] = $v['name'];
                $val['types'] = $v['types'];
                $val['apiid'] = $apiid;
                $val['intro'] = $v['intro'];
                $val['ismust'] = aval($v, 'ismust');
                $val['default'] = aval($v, 'default');
                self::apiRequestSave($val);
            }

            //添加返回参数
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'row.id', 'types' => 2, 'intro' => '自增ID']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'row.createtime', 'types' => 2, 'intro' => '创建时间(时间戳)']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'row._createtime', 'types' => 5, 'intro' => '创建时间(日期时间格式)']);

            //开启后台审核
            if ($group['cpcheck'] == 1) {
                self::apiResponseSave(['apiid' => $apiid, 'name' => 'row.ischeck', 'types' => 2, 'intro' => '审核状态(1已审核，2未审核)']);
                self::apiResponseSave(['apiid' => $apiid, 'name' => 'row._ischeck', 'types' => 1, 'intro' => '审核状态对应文字']);
            }

            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';

            //处理所有展示字段
            $result = Forms::allValidFields($formid);
            $allFields = $result->getData()['allValidFields'];
            $arr = [];
            foreach ($allFields as $v) {
                if ($v['infront'] != 1) {
                    continue;
                }
                $intro = $v['title'];
                if (!empty($v['units'])) {
                    $intro .= '(度量单位：' . $v['units'] . ')';
                }
                if (!empty($v['intro'])) {
                    $intro .= '(说明：' . $v['intro'] . ')';
                }
                if (!empty($v['rules'])) {
                    $intro .= '(';
                    foreach (unserialize($v['rules']) as $k1 => $v1) {
                        $intro .= $k1 . ':' . $v1 . ',';
                    }
                    $intro .= ')';
                }

                self::apiResponseSave([
                    'apiid' => $apiid,
                    'fieldid' => $v['id'],
                    'name' => 'row.' . $v['identifier'],
                    'types' => self::responseDataType($v['datatype'], 1),
                    'intro' => $intro
                ]);
                self::apiResponseSave([
                    'apiid' => $apiid,
                    'fieldid' => $v['id'],
                    'name' => 'row._' . $v['identifier'],
                    'types' => self::responseDataType($v['datatype'], 2),
                    'intro' => $v['title'] . '对应文字'
                ]);

                if ($group['cpcheck'] == 1) {
                    $arr['ischeck'] = 1;
                    $arr['_ischeck'] = '已审核';
                }

                $arr[$v['identifier']] = self::responseDataDemoType($v['datatype'], 1);
                $arr['_' . $v['identifier']] = self::responseDataDemoType($v['datatype'], 2);
                if (!empty($v['units'])) {
                    self::apiResponseSave([
                        'apiid' => $apiid,
                        'fieldid' => $v['id'],
                        'name' => 'row.' . $v['identifier'] . '_units',
                        'types' => 1,
                        'intro' => $v['title'] . '对应度量单位'
                    ]);
                    $arr[$v['identifier'] . '_units'] = $v['units'];
                }
            }
            $dataDemo['data']['row'] = $arr;
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 表单添加修改接口生成
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave3(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '添加修改';
            $data['path'] = 'api/dataSave';
            $data['method'] = 1;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            $data = [
                [
                    'name' => 'fid',
                    'types' => 2,
                    'intro' => '固定值：' . $formid,
                    'ismust' => 1,
                ],
                [
                    'name' => 'id',
                    'types' => 2,
                    'intro' => '信息ID(编辑时传此值)',
                ],
            ];
            foreach ($data as $v) {
                $val = [];
                $val['name'] = $v['name'];
                $val['types'] = $v['types'];
                $val['apiid'] = $apiid;
                $val['intro'] = $v['intro'];
                $val['ismust'] = aval($v, 'ismust');
                $val['default'] = aval($v, 'default');
                self::apiRequestSave($val);
            }

            //提交字段
            $result = Forms::allValidFields($formid);
            $allFields = $result->getData()['allValidFields'];
            foreach ($allFields as $v) {
                if ($v['infront'] != 1) {
                    continue;
                }
                $val = [];
                $val['name'] = $v['identifier'];
                $val['types'] = self::paramType($v['datatype']);
                $val['apiid'] = $apiid;
                $val['ismust'] = $v['required'] == 1 ? 1 : -1;
                $val['default'] = $v['default'];
                $val['intro'] = $v['title'];
                if (!empty($v['rules'])) {
                    foreach (unserialize($v['rules']) as $k1 => $v1) {
                        $val['intro'] .= '(' . $k1 . ':' . $v1 . ',)';
                    }
                }
                $val['fieldid'] = $v['id'];
                self::apiRequestSave($val);
            }


            //添加返回参数
            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';
            $dataDemo['data'] = [];
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 表单删除接口生成
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave4(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '删除';
            $data['path'] = 'api/dataDel';
            $data['method'] = 1;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            $data = [
                [
                    'name' => 'fid',
                    'types' => 2,
                    'intro' => '固定值：' . $formid,
                    'ismust' => 1,
                ],
                [
                    'name' => 'ids',
                    'types' => 1,
                    'ismust' => 1,
                    'intro' => '信息ID(多个用","隔开)',
                ],
            ];
            foreach ($data as $v) {
                $val = [];
                $val['name'] = $v['name'];
                $val['types'] = $v['types'];
                $val['apiid'] = $apiid;
                $val['intro'] = $v['intro'];
                $val['ismust'] = aval($v, 'ismust');
                $val['default'] = aval($v, 'default');
                self::apiRequestSave($val);
            }

            //添加返回参数
            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';
            $dataDemo['data'] = [];
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 表单导出接口生成
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave5(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '导出';
            $data['path'] = 'api/dataExport';
            $data['method'] = 2;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            $data = [
                [
                    'name' => 'fid',
                    'types' => 2,
                    'intro' => '固定值：' . $formid,
                    'ismust' => 1,
                ],
                [
                    'name' => 'page',
                    'types' => 2,
                    'intro' => '翻页',
                    'default' => '1',
                ],
                [
                    'name' => 'pagesize',
                    'types' => 2,
                    'intro' => '一页显示数量',
                    'default' => '1000',
                ],
            ];
            foreach ($data as $v) {
                $val = [];
                $val['name'] = $v['name'];
                $val['types'] = $v['types'];
                $val['apiid'] = $apiid;
                $val['intro'] = $v['intro'];
                $val['ismust'] = aval($v, 'ismust');
                $val['default'] = aval($v, 'default');
                self::apiRequestSave($val);
            }

            $result1 = Forms::searchFields($formid);//搜索条件显示
            $searchFields = $result1->getData()['searchFields'];
            foreach ($searchFields as $v) {
                if (in_array($v['datatype'], ['date', 'datetime'])) {
                    $data = [];
                    $data['name'] = $v['identifier'] . '_s';
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(开始时间，格式：' . date('Y-m-d', TIMESTAMP) . ')';
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);

                    $data = [];
                    $data['name'] = $v['identifier'] . '_e';
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(截止时间，格式：' . date('Y-m-d', TIMESTAMP) . ')';
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                } elseif (in_array($v['datatype'], ['checkbox'])) {
                    $data = [];
                    $data['name'] = $v['identifier'];
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(多个筛选用“`”隔开，如:1`2)';
                    foreach (unserialize($v['rules']) as $k1 => $v1) {
                        $data['intro'] .= '(' . $k1 . ':' . $v1 . ',)';
                    }
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                } else {
                    $data = [];
                    $data['name'] = $v['identifier'];
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['default'] = '';
                    $data['intro'] = $v['title'];
                    if (!empty($v['rules'])) {
                        foreach (unserialize($v['rules']) as $k1 => $v1) {
                            $data['intro'] .= '(' . $k1 . ':' . $v1 . ',)';
                        }
                    }
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                }
            }

            //添加返回参数
            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';
            $dataDemo['data'] = [];
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 表单结构接口生成
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave6(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '表单结构';
            $data['path'] = 'api/dataForm';
            $data['method'] = 2;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            $data = [
                [
                    'name' => 'fid',
                    'types' => 2,
                    'intro' => '固定值：' . $formid,
                    'ismust' => 1,
                ],
                [
                    'name' => 'id',
                    'types' => 2,
                    'intro' => '信息ID（编辑时传此值）',
                ],
            ];
            foreach ($data as $v) {
                $val = [];
                $val['name'] = $v['name'];
                $val['types'] = $v['types'];
                $val['apiid'] = $apiid;
                $val['intro'] = $v['intro'];
                $val['ismust'] = aval($v, 'ismust');
                $val['default'] = aval($v, 'default');
                self::apiRequestSave($val);
            }

            //添加返回参数
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'fieldshtml.id', 'types' => 2, 'intro' => '表单字段ID']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'fieldshtml.title', 'types' => 1, 'intro' => '字段说明']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'fieldshtml.identifier', 'types' => 1, 'intro' => '字段名称']);
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'fieldshtml.field', 'types' => 1, 'intro' => '表单HTML代码']);

            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';
            $formhtml = self::$request->htmlspecialchars('<input type="text"  sucmsg="" datatype="*" nullmsg="请输入标题" placeholder="请输入标题"  class="form-control" id="title" name="title" value="" >');
            $dataDemo['data']['fieldshtml'][] = ['id' => '179', 'title' => '标题', 'identifier' => 'title', 'field' => $formhtml];
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 表单审核接口生成
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave7(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '审核';
            $data['path'] = 'api/dataCheck';
            $data['method'] = 1;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            $data = [
                [
                    'name' => 'fid',
                    'types' => 2,
                    'intro' => '固定值：' . $formid,
                    'ismust' => 1,
                ],
                [
                    'name' => 'id',
                    'types' => 2,
                    'intro' => '信息ID(多个用","隔开)',
                    'ismust' => 1,
                ],
                [
                    'name' => 'ischeck',
                    'types' => 2,
                    'intro' => '审核状态(2不审核，1审核)',
                    'ismust' => 1,
                ],
            ];
            foreach ($data as $v) {
                $val = [];
                $val['name'] = $v['name'];
                $val['types'] = $v['types'];
                $val['apiid'] = $apiid;
                $val['intro'] = $v['intro'];
                $val['ismust'] = aval($v, 'ismust');
                $val['default'] = aval($v, 'default');
                self::apiRequestSave($val);
            }

            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';
            $dataDemo['data'] = [];
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 表单统计接口生成
     * @param int $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiSave8(int $formid): OutputInterface
    {
        if (empty($formid)) {
            return self::$output->withCode(21003);
        }
        $openapiid = (int)str_replace('apiSave', '', __FUNCTION__);//前端开放接口分类ID
        $group = self::t('forms')->withWhere($formid)->fetch();
        if (empty($group)) {
            return self::$output->withCode(21001);
        }
        $openids = !empty($group['openapi']) ? explode(',', $group['openapi']) : [];
        if (in_array($openapiid, $openids)) {
            //如果已经自动添加过,不再重复添加
            $count = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->count();
            if ($count) {
                return self::$output->withCode(200);
            }

            $groupid = self::apiGroupSave($group['name'], $formid)->getData()['id'];

            //添加编辑接口
            $data = [];
            $data['apiname'] = $group['name'] . '统计';
            $data['path'] = 'api/dataCount';
            $data['method'] = 2;
            $data['groupid'] = $groupid;
            $data['formid'] = $formid;
            $data['openapiid'] = $openapiid;
            $res = self::apiSave($data);
            if ($res->getCode() != 200) {
                return $res;
            }
            $apiid = (int)$res->getData()['id'];

            //添加请求参数
            $val = [];
            $val['name'] = 'fid';
            $val['types'] = 2;
            $val['apiid'] = $apiid;
            $val['intro'] = '固定值：' . $formid;
            $val['ismust'] = 1;
            self::apiRequestSave($val);

            $result1 = Forms::searchFields($formid);//搜索条件显示
            $searchFields = $result1->getData()['searchFields'];
            foreach ($searchFields as $v) {
                if (in_array($v['datatype'], ['date', 'datetime'])) {
                    $data = [];
                    $data['name'] = $v['identifier'] . '_s';
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(开始时间，格式：' . date('Y-m-d', TIMESTAMP) . ')';
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);

                    $data = [];
                    $data['name'] = $v['identifier'] . '_e';
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(截止时间，格式：' . date('Y-m-d', TIMESTAMP) . ')';
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                } elseif (in_array($v['datatype'], ['checkbox'])) {
                    $data = [];
                    $data['name'] = $v['identifier'];
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['intro'] = $v['title'] . '(多个筛选用“`”隔开，如:1`2)';
                    foreach (unserialize($v['rules']) as $k1 => $v1) {
                        $data['intro'] .= '(' . $k1 . ':' . $v1 . ',)';
                    }
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                } else {
                    $data = [];
                    $data['name'] = $v['identifier'];
                    $data['types'] = self::paramType($v['datatype'], $v['fieldtype']);
                    $data['apiid'] = $apiid;
                    $data['default'] = '';
                    $data['intro'] = $v['title'];
                    if (!empty($v['rules'])) {
                        foreach (unserialize($v['rules']) as $k1 => $v1) {
                            $data['intro'] .= '(' . $k1 . ':' . $v1 . ',)';
                        }
                    }
                    $data['fieldid'] = $v['id'];
                    self::apiRequestSave($data);
                }
            }

            //添加返回参数
            self::apiResponseSave(['apiid' => $apiid, 'name' => 'count', 'types' => 2, 'intro' => '数量']);

            $dataDemo = [];
            $dataDemo['code'] = 200;
            $dataDemo['msg'] = '操作成功';
            $dataDemo['data']['count'] = 0;
            //返回数据示例保存
            $dataDemoJson = json_encode($dataDemo, JSON_UNESCAPED_UNICODE);
            self::t('apilist')->withWhere($apiid)->update(['result' => $dataDemoJson]);
        } else {
            //没开放前端接口，删除相应分组
            self::apiDelByFormid($formid, $openapiid);
        }

        //没开放前端接口，删除相应分组
        if (empty($group['openapi'])) {
            self::t('apigroup')->withWhere(['formid' => $formid])->delete();
        }

        return self::$output->withCode(200);
    }

    /**
     * 接口添加编辑
     * @param array $param
     * @return OutputInterface
     */
    public static function apiSave(array $param): OutputInterface
    {
        if (empty($param['apiname']) || empty($param['path']) || empty($param['groupid']) || empty($param['method'])) {
            return self::$output->withCode(21003);
        }
        $data = [];
        $data['id'] = aval($param, 'id');
        $data['apiname'] = $param['apiname'];
        $data['path'] = $param['path'];
        $data['groupid'] = $param['groupid'];
        $data['method'] = $param['method'];
        $data['formid'] = aval($param, 'formid');
        $data['openapiid'] = aval($param, 'openapiid');
        $data['ischeck'] = aval($param, 'ischeck', 1);
        return Forms::dataSave(16, '', $data);
    }

    /**
     * 获取接口唯一标识符
     * @param $path
     * @param string $formid
     * @param string $openapiid
     */
    public static function getIdentifier(...$param)
    {
        if (empty($param)) {
            return self::$output->withCode(21003);
        }
        if (is_array($param[0])) {
            $arr = $param[0];
        } else {
            $arr = [];
            $arr[] = $param[0];
            !empty($param[1]) && $arr[] = $param[1];
            !empty($param[2]) && $arr[] = $param[2];
        }
        $identifier = md5(implode(',', $arr));
        return self::$output->withCode(200)->withData(['identifier' => $identifier]);
    }

    /**
     * 接口请求参数添加编辑
     * @param array $param
     * @return OutputInterface
     */
    public static function apiRequestSave(array $param): OutputInterface
    {
        if (empty($param['name']) || empty($param['types']) || empty($param['apiid'])) {
            return self::$output->withCode(21003);
        }
        $data = [];
        $data['id'] = aval($param, 'id');
        $data['name'] = $param['name'];
        $data['types'] = $param['types'];
        $data['apiid'] = $param['apiid'];
        $data['ismust'] = aval($param, 'ismust', -1);
        $data['default'] = aval($param, 'default');
        $data['fieldid'] = aval($param, 'fieldid');
        $data['intro'] = aval($param, 'intro');
        return Forms::dataSave(18, '', $data);
    }

    /**
     * 接口响应参数添加编辑
     * @param array $param
     * @return OutputInterface
     */
    public static function apiResponseSave(array $param): OutputInterface
    {
        if (empty($param['name']) || empty($param['types']) || empty($param['apiid'])) {
            return self::$output->withCode(21003);
        }
        $data = [];
        $data['id'] = aval($param, 'id');
        $data['name'] = $param['name'];
        $data['types'] = $param['types'];
        $data['apiid'] = $param['apiid'];
        $data['fieldid'] = aval($param, 'fieldid');
        $data['intro'] = aval($param, 'intro');
        return Forms::dataSave(19, '', $data);
    }

    /**
     * 通过表单ID删除接口
     * @param int $formid
     * @param int $openapiid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiDelByFormid(int $formid, int $openapiid): OutputInterface
    {
        if (empty($formid) || empty($openapiid)) {
            return self::$output->withCode(21003);
        }
        $row = self::t('apilist')->withWhere(['formid' => $formid, 'openapiid' => $openapiid])->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        return self::apiDel((int)$row['id']);
    }

    /**
     * 通过接口ID删除接口
     * @param int $id
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private static function apiDel(int $id): OutputInterface
    {
        if (empty($id)) {
            return self::$output->withCode(21003);
        }
        self::t('apilist')->withWhere($id)->delete();
        self::t('apirequest')->withWhere(['apiid' => $id])->delete();
        self::t('apiresponse')->withWhere(['apiid' => $id])->delete();
        return self::$output->withCode(200);
    }

    /**
     * 接口分组添加
     * @param string $groupname
     * @param int|null $formid
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function apiGroupSave(string $groupname, int $formid = null): OutputInterface
    {
        if (empty($groupname)) {
            return self::$output->withCode(21003);
        }
        $id = self::t('apigroup')->withWhere(['groupname' => $groupname])->fetch('id');
        if (!$id) {
            $data = [];
            $data['groupname'] = $groupname;
            $formid && $data['formid'] = $formid;
            $id = self::t('apigroup')->insert($data, true);
        }
        return self::$output->withCode(200)->withData(['id' => $id]);
    }
}
