<?php
/**
 * 后台自定义表单管理控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Forms;
use SlimCMS\Core\Page;

class FormsControl extends AdmincpControl
{

    /**
     * 数据列表页
     * @return array|\Psr\Http\Message\ResponseInterface|\SlimCMS\Interfaces\OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataList()
    {
        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int', 'order' => 'string', 'by' => 'string', 'ischeck' => 'int']);
        $fid = (int)aval($param, 'fid');
        $this->checkAllow('dataList' . $fid);
        $param['inlistField'] = 'inlistcp';
        $res = Forms::dataList($param);
        if ($res->getCode() != 200) {
            return self::response($res);
        }
        $data = $res->getData();
        $data['mult'] = Page::multi($data['count'], $data['pagesize'], $data['page'], $data['currenturl'], $data['maxpages'], 5, true, true);
        //处理展示字段
        $res = Forms::listFields($fid)->withData($data);
        //搜索条件显示
        $res = Forms::searchFields($fid)->withData($res->getData());
        //参与排序
        $output = Forms::orderFields($fid)->withData($res->getData());

        $template = '';
        $p = self::input('p');
        if (is_file(CSTEMPLATE . CURSCRIPT . '/' . trim($p, '/') . '/' . $fid . '.htm')) {
            $template = trim($p, '/') . '/' . $fid;
        }
        return $this->view($output, $template);
    }

    /**
     * 表单添加修改页
     * @return array|\cs090\core\数据|string
     */
    public function dataSave()
    {
        $fid = (int)self::input('fid', 'int');
        $id = self::input('id', 'int');
        $this->checkAllow('dataSave' . $fid);
        $res = Forms::dataFormHtml($fid, $id);
        if ($res->getCode() != 200) {
            return self::response($res);
        }
        $template = '';
        $p = self::input('p');
        if (is_file(CSTEMPLATE . CURSCRIPT . '/' . trim($p, '/') . '/' . $fid . '.htm')) {
            $template = trim($p, '/') . '/' . $fid;
        }
        return $this->output($res, $template);
    }

    /**
     * 数据添加修改操作
     * @return array
     */
    public function dataSaveSubmit()
    {
        $did = self::input('did', 'int');
        $id = self::input('id', 'int');
        $referer = self::input('referer', 'url');
        $this->checkAllow('dataSave' . $did);
        return DiyformsModel::dataSave($did, $id, '', $referer);
    }

    /**
     * 数据审核
     * @return array
     */
    public function dataCheck()
    {
        $did = self::input('did', 'int');
        $ids = self::input('ids');
        $ischeck = self::input('ischeck', 'int');
        $this->checkAllow('dataCheck' . $did);
        return DiyformsModel::dataCheck($did, $ids, $ischeck);
    }

    /**
     * 数据删除
     * @return array
     */
    public function dataDel()
    {
        $did = self::input('did', 'int');
        $ids = self::input('ids');
        $this->checkAllow('dataDel' . $did);
        return DiyformsModel::dataDel($did, $ids);
    }

    /**
     * 数据导出
     */
    public function dataExport()
    {
        $param = self::input(['did' => 'int', 'page' => 'int', 'pagesize' => 'int']);
        $this->checkAllow('dataExport' . $param['did']);
        $res = DiyformsModel::dataExport($param);
        $res = Output::exportData($res['data']);
        if ($res['code'] != 200) {
            return $res;
        }

        $file = fopen($res['data'], 'r');
        $filesize = filesize($res['data']);
        ob_end_clean();
        header('Content-type: application/octet-stream');
        header('Accept-Ranges: bytes');
        header('Accept-Length:' . $filesize);
        header("Content-Disposition: attachment; filename=" . basename($res['data']));
        echo fread($file, $filesize);
        fclose($file);
        exit;
    }
}