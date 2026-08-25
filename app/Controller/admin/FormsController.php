<?php
/**
 * 后台表单控制器
 * 对应原 Forms 通用 CRUD 体系，路由规则改为 RESTful
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\AuthService;
use Slim\App;
use SlimCMS\Core\Forms;
use SlimCMS\Core\Page;
use SlimCMS\Error\TextException;

class FormsController extends AdminController
{
    use \SlimCMS\Traits\Url;

    private Page $page;
    private Forms $forms;
    private array $config;//后台配置参数

    public function __construct(App $app, AuthService $authService, Forms $forms, Page $page)
    {
        parent::__construct($app, $authService);
        $this->page = $page;
        $this->forms = $forms;
        $this->config = $this->container->get('cfg');
    }

    /**
     * 表单服务,设置请求对象，用于将中间件中数据传进去
     * @return Forms
     */
    private function forms()
    {
        return $this->forms->setRequest($this->request);
    }

    /**
     * 数据列表页
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws TextException
     */
    public function dataList()
    {
        $param = $this->input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int', 'order' => 'string', 'by' => 'string', 'ischeck' => 'int']);
        $fid = (int)aval($param, 'fid');
        if($r = $this->checkAllow('dataList' . $fid)){
            return $r;
        }
        $res = $this->forms()->dataList($param);
        if ($res->getCode() != 200) {
            try {
                return $this->directTo($res);
            } catch (TextException $e) {
                return $this->directTo($res->withReferer('/admin/index'));
            }
        }

        $data = $res->getData();
        $data['admin'] = $this->adminInfo()->toArray();
        $data['admin']['purviews'] = $this->adminInfo()->groupidEntity()->isSuperAdmin() ? [] : $this->adminInfo()->groupidEntity()->getPurviewsList();
        $data['mult'] = $this->page->multi($data['count'], $data['pagesize'], $data['page'], $data['currenturl'], $data['maxpages'], 5, true, true);
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
        if($r = $this->checkAllow('dataSave' . $fid)){
            return $r;
        }
        if ($this->request->getMethod() === 'POST') {
            $ccode = $this->config['ccode'] == '1' ? $this->inputString('ccode') : null;
            $referer = $this->input('referer', 'url');
            $referer = $referer ?: $this->url('&id=', '/admin/forms/dataList');
            $res = $this->forms()->formVerify($ccode)->dataSave($fid, $id)->withReferer($referer);
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
     * @return \Psr\Http\Message\ResponseInterface
     * @throws TextException
     */
    public function dataCheck()
    {
        $fid = $this->inputInt('fid');
        $ids = $this->input('ids');
        $ids = is_array($ids) ? $ids : ($ids ? explode(',', $ids) : []);
        $ischeck = $this->inputInt('ischeck');
        if($r = $this->checkAllow('dataCheck' . $fid)){
            return $r;
        }
        $res = $this->forms()->dataCheck($fid, $ids, $ischeck);
        return $this->resp($res);
    }

    /**
     * 数据删除
     * @return \Psr\Http\Message\ResponseInterface
     * @throws TextException
     */
    public function dataDel()
    {
        $fid = $this->inputInt('fid');
        $ids = $this->input('ids');
        $ids = is_array($ids) ? $ids : ($ids ? explode(',', $ids) : []);
        if($r = $this->checkAllow('dataDel' . $fid)){
            return $r;
        }
        $res = $this->forms()->dataDel($fid, $ids);
        return $this->resp($res);
    }

    /**
     * 数据导出
     */
    public function dataExport()
    {
        $param = $this->input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int']);
        if($r = $this->checkAllow('dataExport' . $param['fid'])){
            return $r;
        }
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
