<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Controller\main;

use App\Core\Forms;
use App\Core\Ueditor;
use Psr\Http\Message\ResponseInterface;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\ImageCode;

class MainController extends ControlAbstract
{
    /**
     * 后台仪表盘
     * 当前管理员信息通过 AdminAuthMiddleware 注入的 request attribute 'admin' 获取
     */
    public function index(): ResponseInterface
    {
        return $this->view(self::$output, 'index');
    }

    /**
     * 联动菜单数据
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function enumsData()
    {
        $egroup = self::input('egroup');
        $res = Forms::enumsData($egroup);
        return self::response($res);
    }

    /**
     * 生成验证码
     */
    public function captcha()
    {
        ImageCode::doimg();
    }

    /**
     * 获取 FORMHASH（用于 AJAX 请求）
     */
    public function formHash(): ResponseInterface
    {
        $data = ['formHash' => self::$request->getFormHash()];
        $output = self::$output->withData($data);
        return self::response($output);
    }

    /**
     * 兜底处理：所有未匹配的路由统一返回友好错误页面
     */
    public function notFound(): ResponseInterface
    {
        if (strpos(self::$request->getRequest()->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->json(self::$output->withCode(21009));
        }
        return $this->view(self::$output, 'error');
    }

    /**
     * Ueditor编辑码执行程序
     */
    public function ueditor()
    {
        $action = self::input('action');
        $water = self::input('needwatermark') ? true : false;
        switch ($action) {
            case 'config':
                $result = Ueditor::config();
                break;
            case 'uploadimage':
                $result = Ueditor::upload('imageFieldName', 'image', $water);
                break;
            case 'uploadscrawl':
                $result = Ueditor::upload('scrawlFieldName');
                break;
            case 'uploadvideo':
                $result = Ueditor::upload('videoFieldName', 'media');
                break;
            case 'uploadfile':
                $result = Ueditor::upload('fileFieldName', 'addon');
                break;
            case 'listfile':
            case 'listimage':
                $size = self::input('size', 'int');
                $start = self::input('start', 'int');
                $result = Ueditor::listdata($size, $start);
                break;
        }
        if (!empty($result)) {
            echo json_encode($result->getData());
        }
        exit;
    }
}
