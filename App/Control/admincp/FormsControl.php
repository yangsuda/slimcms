<?php
/**
 * 后台自定义表单管理控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Forms;
use SlimCMS\Core\Page;
use SlimCMS\Helper\File;
use SlimCMS\Interfaces\OutputInterface;

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
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataSave()
    {
        $fid = (int)self::input('fid', 'int');
        $id = (int)self::input('id', 'int');
        $this->checkAllow('dataSave' . $fid);
        $formhash = self::input('formhash');
        if ($formhash) {
            //如启用验证码，对验证码验证
            if (self::$config['ccode'] == '1') {
                $ccode = (string)self::input('ccode');
                $img = new \Securimage();
                if (!$img->check($ccode)) {
                    $output = self::$output->withCode(24023);
                    return $this->directTo($output);
                }
            }
            $res = Forms::submitCheck($formhash);
            if ($res->getCode() != 200) {
                return $this->directTo($res);
            }
            $referer = self::input('referer', 'url');
            $referer = $referer ?: self::url('&p=forms/dataList&id=');
            $res = Forms::dataSave($fid, $id)->withReferer($referer);
            return $this->directTo($res);
        }
        $options = ['cacheTime' => 300, 'ueditorType' => 'admin'];
        $res = Forms::dataFormHtml($fid, $id, $options);
        if ($res->getCode() != 200) {
            return self::response($res);
        }
        $template = '';
        $p = self::input('p');
        if (is_file(CSTEMPLATE . CURSCRIPT . '/' . trim($p, '/') . '/' . $fid . '.htm')) {
            $template = trim($p, '/') . '/' . $fid;
        }
        return $this->view($res, $template);
    }

    /**
     * 数据审核
     * @return array
     */
    public function dataCheck()
    {
        $fid = (int)self::input('fid', 'int');
        $ids = self::input('ids');
        $ids = is_array($ids) ? $ids : ($ids ? explode(',', $ids) : '');
        $ischeck = (int)self::input('ischeck', 'int');
        $this->checkAllow('dataCheck' . $fid);
        $res = Forms::dataCheck($fid, $ids, $ischeck);
        return self::response($res);
    }

    /**
     * 数据删除
     * @return array
     */
    public function dataDel()
    {
        $fid = (int)self::input('fid', 'int');
        $ids = self::input('ids');
        $ids = is_array($ids) ? $ids : ($ids ? explode(',', $ids) : '');
        $this->checkAllow('dataDel' . $fid);
        $referer = self::url('&p=forms/dataList&ids=');
        $res = Forms::dataDel($fid, $ids)->withReferer($referer);
        return $this->directTo($res);
    }

    /**
     * 数据导出
     */
    public function dataExport()
    {
        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int']);
        $this->checkAllow('dataExport' . $param['fid']);
        $res = Forms::dataExport($param);
        $data = $res->getData();
        $response = self::$response->getResponse();
        if (self::input('down') == 1) {
            if (!is_file($data['file'])) {
                $output = self::$output->withCode(21050);
                return $this->directTo($output);
            }
            $file = fopen($data['file'], 'r');
            $filesize = filesize($data['file']);
            ob_end_clean();
            $response = $response
                ->withHeader('Content-type', 'application/octet-stream')
                ->withHeader('Accept-Ranges', 'bytes')
                ->withHeader('Accept-Length', $filesize)
                ->withHeader('Content-Disposition', 'attachment; filename=' . basename($data['file']));
            $content = fread($file, $filesize);
            fclose($file);
        } else {
            $response = $response->withHeader('Content-type', 'text/html');
            $content = $data['text'] . '<script>location="' . $res->getReferer() . '";</script>';
        }
        $response->getBody()->write($content);
        return $response;
    }
}