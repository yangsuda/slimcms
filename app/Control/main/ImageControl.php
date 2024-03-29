<?php

/**
 * 图片处理控制类,防止前端直接删除图片，默认相应接口不开放
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\main;

use App\Core\Forms;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\Crypt;
use SlimCMS\Interfaces\UploadInterface;

class ImageControl extends ControlAbstract
{
    /**
     * 图片动态加载
     */
    public function dynamicImage()
    {
        header('Content-type: image/jpeg');
        $key = self::inputString('key');//由pic、time参数组成的加密数组
        $arr = Crypt::decrypt($key);
        //图片生命周期5秒，防止链接复制出去被人访问
        if (
            !empty($arr['pic'])
            && !empty($arr['time'])
            && is_file(CSPUBLIC . $arr['pic'])
            && !empty($arr['pic'])
            && $arr['time'] + 5 > TIMESTAMP
        ) {
            $pic = parse_url(copyImage($arr['pic']),PHP_URL_PATH);
            $str = file_get_contents(CSPUBLIC . $pic);
        } else {
            $str = file_get_contents(CSPUBLIC . 'resources/global/images/nopic/nopic.jpg');
        }
        exit($str);
    }

    /**
     * 图片上传组件传图
     */
    /*public function webupload()
    {
        $post = [];
        $post['files'] = aval($_FILES, 'file');
        $post['water'] = self::input('water') ? true : false;
        $post['fileid'] = self::input('id');
        $upload = self::$container->get(UploadInterface::class);
        $res = $upload->webupload($post);
        if ($res->getCode() != 200) {
            echo '上传失败:' . $res->getMsg();
        } else {
            echo $res->getData()['fileid'];
        }
        exit;
    }*/

    /**
     * 删除传图组件指定图片
     */
    /*public function webuploadDel()
    {
        isset($_SESSION) ? '' : session_start();
        $id = self::input('id');
        if (!isset($_SESSION['bigfile_info'][$id])) {
            exit();
        }
        $upload = self::$container->get(UploadInterface::class);
        $upload->uploadDel($_SESSION['bigfile_info'][$id]);
        unset($_SESSION['file_info'][$id]);
        unset($_SESSION['bigfile_info'][$id]);
        exit("已删除");
    }*/

    /**
     * 传图组件缩略图显示
     */
    /*public function webuploadThumbnail()
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
    }*/

    /**
     * 删除图集中的某张图片
     * @throws \SlimCMS\Error\TextException
     */
    /*public function webuploadImageDel()
    {
        $fid = self::inputInt('fid');
        $id = self::inputInt('id');
        $field = self::inputString('field');
        $pic = self::inputString('pic');
        $res = Forms::imgsDel($fid, $id, $field, $pic);
        return self::response($res);
    }*/
}
