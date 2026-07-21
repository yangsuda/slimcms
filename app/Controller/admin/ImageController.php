<?php
declare(strict_types=1);

namespace App\Controller\admin;

use App\Core\Forms;
use App\Service\admin\MainService;
use SlimCMS\Interfaces\UploadInterface;

class ImageController extends AdminController
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
        $upload = self::$container->get(UploadInterface::class);
        $res = $upload->webupload($post);
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
        $id = self::input('id');
        if (!isset($_SESSION['bigfile_info'][$id])) {
            exit();
        }
        $upload = self::$container->get(UploadInterface::class);
        $upload->uploadDel($_SESSION['bigfile_info'][$id]);
        unset($_SESSION['file_info'][$id]);
        unset($_SESSION['bigfile_info'][$id]);
        exit("已删除");
    }

    /**
     * 传图组件缩略图显示
     */
    public function webuploadThumbnail()
    {
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
        $fid = self::inputInt('fid');
        $id = self::inputInt('id');
        $field = self::inputString('field');
        $pic = self::inputString('pic');
        $res = Forms::imgsDel($fid, $id, $field, $pic);
        return self::response($res);
    }

    /**
     * 超大附件上传
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function superFileUpload()
    {
        $this->checkAllow();
        $file = aval($_FILES, 'file');
        $index = self::inputInt('index');
        $filename = self::inputString('filename');
        $upload = self::$container->get(UploadInterface::class);
        $res = $upload->superFileUpload($file, $index, $filename, 'superFile');
        return $this->json($res);
    }

    /**
     * 删除附件图片
     * @return array
     */
    public function delImg()
    {
        $this->checkAllow();
        $param = self::input(['fid' => 'int', 'id' => 'int', 'identifier' => 'string']);
        $res = MainService::delImg($param);
        return self::response($res);
    }
}
