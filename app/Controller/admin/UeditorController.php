<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\AuthService;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use SlimCMS\Core\Ueditor;

class UeditorController extends AdminController
{
    private Ueditor $ueditor;

    public function __construct(App $app, AuthService $authService, Ueditor $ueditor)
    {
        parent::__construct($app, $authService);
        $this->ueditor = $ueditor;
    }

    /**
     * Ueditor编辑码执行程序
     * @return ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function ueditor(): ResponseInterface
    {
        if($r = $this->checkAllow('')){
            return $r;
        }
        $action = $this->inputString('action');
        $water = $this->input('needwatermark') ? true : false;
        $result = null;
        switch ($action) {
            case 'config':
                $result = $this->ueditor->config();
                break;
            case 'uploadimage':
                $result = $this->ueditor->upload('imageFieldName', 'image', $water);
                break;
            case 'uploadscrawl':
                $result = $this->ueditor->upload('scrawlFieldName');
                break;
            case 'uploadvideo':
                $result = $this->ueditor->upload('videoFieldName', 'media');
                break;
            case 'uploadfile':
                $result = $this->ueditor->upload('fileFieldName', 'addon');
                break;
            case 'listfile':
            case 'listimage':
                $size = $this->inputInt('size');
                $start = $this->inputInt('start');
                $result = $this->ueditor->listdata($size, $start);
                break;
        }
        $response = $this->response->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode($result ? $result->getData() : []));
        return $response;
    }
}
