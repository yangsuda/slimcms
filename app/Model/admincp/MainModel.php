<?php

declare(strict_types=1);

namespace App\Model\admincp;

use App\Core\Upload;
use SlimCMS\Abstracts\ModelAbstract;
use App\Core\Forms;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Helper\Ipdata;

class MainModel extends ModelAbstract
{
    /**
     * 恢复数据
     * @param $id
     * @return array
     */
    public static function recovery(int $id): OutputInterface
    {
        if (empty($id)) {
            return self::$output->withCode(21002);
        }
        $row = self::t('archivedata')->withWhere($id)->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        $content = unserialize($row['content']);
        $table = self::t('forms')->withWhere($row['formid'])->fetch('table');
        self::t($table)->insert($content);
        self::t('archivedata')->withWhere($id)->delete();
        return self::$output->withCode(200, 211031);
    }

    /**
     * 指定此三个文件夹文件做安全校验
     */
    public static function fileVerify(): OutputInterface
    {
        $dirs = ['app', 'template', 'vendor'];
        foreach ($dirs as $dir) {
            self::getFiles(CSROOT . $dir);
        }
        //检测是否有删除
        $list = self::t('fileverify')->fetchList();
        foreach ($list as $v) {
            if (!is_file(CSROOT . $v['filename'])) {
                self::t('fileverify')->withWhere($v['id'])->update(['status' => 4]);
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 遍历相关文件夹并将所有文件key入库
     * @param $directory
     */
    private static function getFiles($directory)
    {
        $exempt = ['.', '..', '.ds_store', '.svn'];
        $directory = preg_replace("/\/$/", "", $directory) . '/';
        $handle = opendir($directory);
        while (false !== ($resource = readdir($handle))) {
            if (!in_array(strtolower($resource), $exempt)) {
                //排除目录
                if (is_dir($directory . $resource . '/')) {
                    self::getFiles($directory . $resource . '/');
                } else {
                    $file = $directory . $resource;
                    $srcverifykey = md5_file($file);
                    $filename = str_replace(CSROOT, '', $file);
                    $row = self::t('fileverify')->withWhere(['filename' => $filename])->fetch();
                    if (empty($row)) {
                        $data = ['filename' => $filename, 'srcverifykey' => $srcverifykey, 'status' => 3, 'createtime' => TIMESTAMP, 'ip' => Ipdata::getip()];
                        self::t('fileverify')->insert($data);
                    } else {
                        $status = $row['srcverifykey'] == $srcverifykey ? 1 : 2;
                        $data = ['curverifykey' => $srcverifykey, 'status' => $status];
                        self::t('fileverify')->withWhere($row['id'])->update($data);
                    }
                }
            }
        }
        closedir($handle);
    }

    /**
     * 更新文件校验KEY
     * @param $file
     * @return array
     */
    public static function updateVerifyKey(string $file): OutputInterface
    {
        if (empty($file)) {
            return self::$output->withCode(21002);
        }
        $row = self::t('fileverify')->withWhere(['filename' => $file])->fetch();
        if (empty($row)) {
            return self::$output->withCode(21001);
        }
        if (!is_file(CSROOT . $row['filename'])) {
            self::t('fileverify')->withWhere($row['id'])->delete();
        } else {
            $data = ['srcverifykey' => $row['curverifykey'], 'status' => 1];
            self::t('fileverify')->withWhere($row['id'])->update($data);
        }
        return self::$output->withCode(200);
    }

    /**
     * 删除某个附件
     * @param $param
     * @return array
     */
    public static function delImg(array $param): OutputInterface
    {
        if (empty($param['fid']) || empty($param['id']) || empty($param['identifier'])) {
            return self::$output->withCode(21002);
        }
        $tableName = self::t('forms')->withWhere($param['fid'])->fetch('table');
        $row = self::t($tableName)->withWhere($param['id'])->fetch();
        if (empty($row[$param['identifier']])) {
            return self::$output->withCode(21001);
        }
        Upload::uploadDel($row[$param['identifier']]);
        self::t($tableName)->withWhere($param['id'])->update([$param['identifier'] => '']);
        return self::$output->withCode(200);
    }

    /**
     * 接口文档
     */
    public static function apiIntro(): OutputInterface
    {
        $param = [];
        $param['fid'] = 8;
        $param['pagesize'] = 1000;
        $res = Forms::dataList($param)->withData(['prompts' => self::$output->prompts()]);

        $count = self::t('apiintro')->count();
        $where = [self::t()->field('openapi', '', '<>')];
        $ids = self::t('forms')->withWhere($where)->onefieldList('id');
        if (empty($ids) && empty($count)) {
            return self::$output->withCode(21001);
        }

        $param = [];
        $param['fid'] = 1;
        $param['where'] = ['id' => $ids];
        $param['pagesize'] = 500;
        $result = Forms::dataList($param);
        $data = $result->getData();
        foreach ($data['list'] as &$v) {
            $id = (int)$v['id'];
            $result1 = Forms::listFields($id, 500, 'inlist');//处理展示字段
            $v['listFields'] = $result1->getData()['listFields'];
            $result1 = Forms::searchFields($id);//搜索条件显示
            $v['searchFields'] = $result1->getData()['searchFields'];
            $result1 = Forms::orderFields($id);//排序字段
            $v['orderFields'] = $result1->getData()['orderFields'];
            $result1 = Forms::allValidFields($id);//所有字段
            $v['allFields'] = $result1->getData()['allValidFields'];
        }
        return $res->withData(['forms' => $data['list']]);
    }

    /**
     * 获取开放web功能的规则
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function getOpenWebRule()
    {
        $where = ['formid' => 1, 'identifier' => 'openweb'];
        $rules = self::t('forms_fields')->withWhere($where)->fetch('rules');
        $rules = unserialize($rules);
        return self::$output->withCode(200)->withData(['rules' => $rules]);
    }
}
