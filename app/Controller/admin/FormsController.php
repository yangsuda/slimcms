<?php
/**
 * 后台表单控制器
 * 对应原 Forms 通用 CRUD 体系，路由规则改为 RESTful
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Core\Page;
use SlimCMS\Error\TextException;

class FormsController extends AdminController
{
    /**
     * 数据列表页
     * @return array|\Psr\Http\Message\ResponseInterface|\SlimCMS\Interfaces\OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataList()
    {
        $param = $this->input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int', 'order' => 'string', 'by' => 'string', 'ischeck' => 'int']);
        $fid = (int)aval($param, 'fid');
        $this->checkAllow('dataList' . $fid);
        $res = $this->forms()->dataList($param);
        if ($res->getCode() != 200) {
            try {
                return $this->directTo($res);
            } catch (TextException $e) {
                return $this->directTo($res->withReferer('/admin/index'));
            }
        }

        $data = $res->getData();
        $data['admin'] = $this->admin->toArray();
        $data['admin']['purviews'] = $this->admin->groupidEntity()->isSuperAdmin() ? [] : $this->admin->groupidEntity()->getPurviewsList();
        $data['mult'] = $this->i(Page::class)->multi($data['count'], $data['pagesize'], $data['page'], $data['currenturl'], $data['maxpages'], 5, true, true);
        //处理展示字段
        $res = $this->forms()->listFields($fid)->withData($data);
        //搜索条件显示
        $res = $this->forms()->searchFields($fid)->withData($res->getData());
        //参与排序
        $output = $this->forms()->orderFields($fid)->withData($res->getData());

        $template = '';
        if (is_file(CSTEMPLATE . $this->p . '/' . $fid . '.htm')) {
            $template = $this->p . '/' . $fid;
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
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $this->checkAllow('dataSave' . $fid);
        $formhash = $this->inputString('formhash');
        if ($formhash) {
            $ccode = $this->config['ccode'] == '1' ? $this->inputString('ccode') : null;
            $referer = $this->input('referer', 'url');
            $referer = $referer ?: $this->url('&id=', '/admin/forms/dataList');
            $res = $this->forms()->formVerify($formhash, $ccode)->dataSave($fid, $id)->withReferer($referer);
            return $this->directTo($res);
        }
        $res = $this->forms()->dataFormHtml($fid, $id, ['cacheTime' => 300, 'ueditorType' => 'admin']);
        if ($res->getCode() != 200) {
            try {
                return $this->directTo($res);
            } catch (TextException $e) {
                return $this->directTo($res->withReferer('/admin/index'));
            }
        }
        $template = '';
        if (is_file(CSTEMPLATE . $this->p . '/' . $fid . '.htm')) {
            $template = $this->p . '/' . $fid;
        }
        return $this->view($res, $template);
    }

    /**
     * 数据审核
     * @return \Psr\Http\Message\MessageInterface
     * @throws TextException
     */
    public function dataCheck()
    {
        $fid = $this->inputInt('fid');
        $ids = $this->input('ids');
        $ids = is_array($ids) ? $ids : ($ids ? explode(',', $ids) : []);
        $ischeck = $this->inputInt('ischeck');
        $this->checkAllow('dataCheck' . $fid);
        $res = $this->forms()->dataCheck($fid, $ids, $ischeck);
        return $this->response($res);
    }

    /**
     * 数据删除
     * @return \Psr\Http\Message\MessageInterface
     * @throws TextException
     */
    public function dataDel()
    {
        $fid = $this->inputInt('fid');
        $ids = $this->input('ids');
        $ids = is_array($ids) ? $ids : ($ids ? explode(',', $ids) : []);
        $this->checkAllow('dataDel' . $fid);
        $res = $this->forms()->dataDel($fid, $ids);
        return $this->response($res);
    }

    /**
     * 数据导出
     */
    public function dataExport()
    {
        $param = $this->input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int']);
        $this->checkAllow('dataExport' . $param['fid']);
        $res = $this->forms()->dataExport($param);
        $data = $res->getData();
        if ($this->inputInt('down') == 1) {
            if (!is_file($data['file'])) {
                $output = $this->output->withCode(21050);
                return $this->directTo($output);
            }
            $file = fopen($data['file'], 'r');
            $filesize = filesize($data['file']);
            ob_end_clean();
            $response = $this->response
                ->withHeader('Content-type', 'application/octet-stream')
                ->withHeader('Accept-Ranges', 'bytes')
                ->withHeader('Accept-Length', $filesize)
                ->withHeader('Content-Disposition', 'attachment; filename=' . basename($data['file']));
            $content = fread($file, $filesize);
            fclose($file);
        } else {
            $response = $this->response->withHeader('Content-type', 'text/html');
            $content = $data['text'] . '<script>location="' . $res->getReferer() . '";</script>';
        }
        $response->getBody()->write($content);
        return $response;
    }
}
