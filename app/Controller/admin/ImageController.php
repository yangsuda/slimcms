<?php
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\common\FileService;
use SlimCMS\Interfaces\UploadInterface;

class ImageController extends AdminController
{
    /**
     * 图片上传组件传图
     */
    public function webupload()
    {
        $option = [];
        $option['water'] = $this->input('water') ? true : false;
        $option['fileid'] = $this->input('id');
        $upload = $this->container->get(UploadInterface::class);
        $res = $upload->webupload($this->request->getUploadedFiles()['file'] ?? null, $option);
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
        $id = $this->input('id');
        $bigfile_info = $this->session()->get('bigfile_info', []);
        if (!isset($bigfile_info[$id])) {
            exit();
        }
        $upload = $this->container->get(UploadInterface::class);
        $upload->uploadDel($bigfile_info[$id]);
        unset($bigfile_info[$id]);
        $this->session()->set('bigfile_info', $bigfile_info);
        exit("已删除");
    }

    /**
     * 传图组件缩略图显示
     */
    public function webuploadThumbnail()
    {
        $id = $this->input('id');
        if (empty($id)) {
            exit('No ID');
        }
        $bigfile_info = $this->session()->get('bigfile_info', []);
        if (!isset($bigfile_info[$id])) {
            exit(0);
        }
        $url = $bigfile_info[$id];
        $imagevariable = file_get_contents(CSPUBLIC . str_replace($this->config['basehost'], '', copyImage($url, 120, 120)));
        header('Content-type: image/jpeg');
        header('Content-Length: ' . strlen($imagevariable));
        exit($imagevariable);
    }

    /**
     * 删除图集中的某张图片
     * @throws \SlimCMS\Error\TextException
     */
    public function webuploadImageDel()
    {
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $field = $this->inputString('field');
        $pic = $this->inputString('pic');
        $res = $this->i(FileService::class)->imgsDel($fid, $id, $field, $pic);
        return $this->response($res);
    }

    /**
     * 超大附件上传
     * @return \Psr\Http\Message\MessageInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function superFileUpload()
    {
        $this->checkAllow();
        $file = $this->request->getUploadedFiles()['file'] ?? null;
        $index = $this->inputInt('index');
        $filename = $this->inputString('filename');
        $upload = $this->container->get(UploadInterface::class);
        $res = $upload->superFileUpload($file, $index, $filename, 'superFile');
        return $this->json($res);
    }

    /**
     * 删除附件图片
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function delImg()
    {
        $this->checkAllow();
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $identifier = $this->inputString('identifier');
        $res = $this->FileService()->delImg($fid, $id, $identifier);
        return $this->response($res);
    }

    /**
     * 设置封面
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function webuploadCover()
    {
        $this->checkAllow();
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $pic = $this->inputString('pic');
        $res = $this->FileService()->webuploadCover($fid, $id, $pic);
        return $this->response($res);
    }

    /**
     * 多附件删除
     * @return \Psr\Http\Message\MessageInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SlimCMS\Error\TextException
     */
    public function delFromAddons()
    {
        $this->checkAllow();
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $identifier = $this->inputString('identifier');
        $url = $this->inputString('url');
        $res = $this->FileService()->delFromAddons($fid, $id, $identifier, $url);
        return $this->response($res);
    }

    private function FileService(): FileService
    {
        return $this->i(FileService::class);
    }
}
