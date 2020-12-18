<?php

/**
 * 开放前端WEB表单控制类
 */
declare(strict_types=1);

namespace App\Control\main;

use App\Core\Forms;
use App\Model\admincp\MainModel;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Core\Page;

class FormsControl extends ControlAbstract
{

    /**
     * 前端web开放权限校验
     * @param int $fid
     * @param int $type
     * @return \SlimCMS\Interfaces\OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    private function dataOpenCheck(int $fid, int $type)
    {
        if (empty($fid) || empty($type)) {
            return self::$output->withCode(21027);
        }
        $res = Forms::formView($fid);
        if ($res->getCode() != 200) {
            return $res;
        }
        $form = $res->getData()['form'];
        $arr = !empty($form['openweb']) ? explode(',', $form['openweb']) : [];
        if (empty($arr) || !in_array($type, $arr)) {
            $rules = MainModel::getOpenWebRule()->getData()['rules'];
            return self::$output->withCode(223022,['msg'=>$rules[$type]]);
        }
        return $res;
    }

    /**
     * 数据列表页
     * @return array|\Psr\Http\Message\ResponseInterface|\SlimCMS\Interfaces\OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataList()
    {
        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int', 'order' => 'string', 'by' => 'string', 'ischeck' => 'int']);
        $fid = (int)aval($param, 'fid');
        $res = $this->dataOpenCheck($fid, 1);
        if ($res->getCode() != 200) {
            return self::response($res);
        }
        $param['inlistField'] = 'inlist';
        $res = Forms::dataList($param);
        if ($res->getCode() != 200) {
            return self::response($res);
        }
        $data = $res->getData();
        $data['mult'] = Page::multi($data['count'], $data['pagesize'], $data['page'], $data['currenturl'], $data['maxpages']);
        $data['openweb'] = !empty($data['form']['openweb']) ? explode(',', $data['form']['openweb']) : [];
        //处理展示字段
        $res = Forms::listFields($fid, 100, $param['inlistField'])->withData($data);
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

        $res = $this->dataOpenCheck($fid, 6);
        if ($res->getCode() != 200) {
            return self::response($res);
        }

        $formhash = self::input('formhash');
        if ($formhash) {
            $res = $this->dataOpenCheck($fid, 3);
            if ($res->getCode() != 200) {
                return self::response($res);
            }

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
        $options = ['cacheTime' => 300, 'ueditorType' => 'member'];
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

        $res = $this->dataOpenCheck($fid, 7);
        if ($res->getCode() != 200) {
            return self::response($res);
        }

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

        $res = $this->dataOpenCheck($fid, 4);
        if ($res->getCode() != 200) {
            return self::response($res);
        }

        $referer = self::url('&p=forms/dataList&ids=');
        $res = Forms::dataDel($fid, $ids)->withReferer($referer);
        return $this->directTo($res);
    }

    /**
     * 信息详细
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataView()
    {
        $fid = (int)self::input('fid', 'int');
        $id = (int)self::input('id');

        $res = $this->dataOpenCheck($fid, 2);
        if ($res->getCode() != 200) {
            return self::response($res);
        }

        $res = Forms::dataView($fid, $id);

        $template = '';
        $p = self::input('p');
        if (is_file(CSTEMPLATE . CURSCRIPT . '/' . trim($p, '/') . '/' . $fid . '.htm')) {
            $template = trim($p, '/') . '/' . $fid;
        }
        return $this->view($res, $template);
    }

    /**
     * 数据导出
     */
    public function dataExport()
    {
        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int']);
        $fid = (int)aval($param, 'fid');

        $res = $this->dataOpenCheck($fid, 5);
        if ($res->getCode() != 200) {
            return self::response($res);
        }

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