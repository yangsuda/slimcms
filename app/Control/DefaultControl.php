<?php
/**
 * 默认控制类
 */

namespace App\Control;

use App\Core\Ueditor;
use SlimCMS\Abstracts\ControlAbstract;

class DefaultControl extends ControlAbstract
{
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
        if(!empty($result)){
            echo json_encode($result->getData());
        }
        exit;
    }
}