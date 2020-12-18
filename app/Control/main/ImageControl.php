<?php

/**
 * 图片处理控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\main;

use App\Core\Forms;
use App\Core\Upload;
use SlimCMS\Abstracts\ControlAbstract;

class ImageControl extends ControlAbstract
{
    /**
     * 图片上传组件传图
     */
    public function webupload()
    {
        $post = [];
        $post['files'] = aval($_FILES, 'file');
        $post['water'] = self::input('water') ? true : false;
        $post['fileid'] = self::input('id');
        $res = Upload::webupload($post);
        if ($res->getCode() != 200) {
            echo '上传失败:' . $res->getMsg();
        } else {
            echo $res->getData()['fileid'];
        }
        exit;
    }

    /**
     * 删除传图组件指定图片
     */
    public function webuploadDel()
    {
        isset($_SESSION) ? '' : session_start();
        $id = self::input('id');
        if (!isset($_SESSION['bigfile_info'][$id])) {
            exit();
        }
        Upload::uploadDel($_SESSION['bigfile_info'][$id]);
        unset($_SESSION['file_info'][$id]);
        unset($_SESSION['bigfile_info'][$id]);
        exit("已删除");
    }

    /**
     * 传图组件缩略图显示
     */
    public function webuploadThumbnail()
    {
        isset($_SESSION) ? '' : session_start();
        $id = self::input('id');
        if (empty($id)) {
            exit('No ID');
        }
        if (!isset($_SESSION['file_info'][$id])) {
            exit(0);
        }
        header('Content-type: image/jpeg');
        header('Content-Length: ' . strlen($_SESSION['file_info'][$id]));
        exit($_SESSION['file_info'][$id]);
    }

    /**
     * 删除图集中的某张图片
     * @throws \SlimCMS\Error\TextException
     */
    public function webuploadImageDel()
    {
        $fid = (int)self::input('fid', 'int');
        $id = (int)self::input('id', 'int');
        $field = (string)self::input('field');
        $pic = (string)self::input('pic');
        $res = Forms::imgsDel($fid, $id, $field, $pic);
        return self::response($res);
    }
}