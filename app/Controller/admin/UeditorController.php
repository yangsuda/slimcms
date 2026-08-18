<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Core\Ueditor;

class UeditorController extends AdminController
{
    private function u():Ueditor
    {
        return $this->i(Ueditor::class);
    }
    /**
     * Ueditor编辑码执行程序
     */
    public function ueditor()
    {
        $this->checkAllow('');
        $action = $this->inputString('action');
        $water = $this->input('needwatermark') ? true : false;
        switch ($action) {
            case 'config':
                $result = $this->u()->config();
                break;
            case 'uploadimage':
                $result = $this->u()->upload('imageFieldName', 'image', $water);
                break;
            case 'uploadscrawl':
                $result = $this->u()->upload('scrawlFieldName');
                break;
            case 'uploadvideo':
                $result = $this->u()->upload('videoFieldName', 'media');
                break;
            case 'uploadfile':
                $result = $this->u()->upload('fileFieldName', 'addon');
                break;
            case 'listfile':
            case 'listimage':
                $size = $this->inputInt('size');
                $start = $this->inputInt('start');
                $result = $this->u()->listdata($size, $start);
                break;
        }
        if (!empty($result)) {
            echo json_encode($result->getData());
        }
        exit;
    }
}
