<?php
declare(strict_types=1);

namespace app\Controller\admin;

use app\Service\admin\AuthService;
use app\Service\common\FileService;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use SlimCMS\Interfaces\UploadInterface;

class ImageController extends AdminController
{
    private FileService $fileService;
    private array $config;//后台配置参数
    private UploadInterface $uploader;

    public function __construct(App $app, AuthService $authService, FileService $fileService, UploadInterface $uploader)
    {
        parent::__construct($app, $authService);
        $this->fileService = $fileService;
        $this->config = $this->container->get('cfg');
        $this->uploader = $uploader;
    }

    /**
     * 图片上传组件传图
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function webupload(): ResponseInterface
    {
        $option = [];
        $option['water'] = $this->input('water') ? true : false;
        $option['fileid'] = $this->input('id');
        $file = $this->request->getUploadedFiles()['file'] ?? null;
        // 未选择文件时直接返回上传失败，避免对 Upload::webupload 传入 null 触发 TypeError
        $res = $file !== null
            ? $this->uploader->webupload($file, $option)
            : $this->output->withCode(23001);
        $body = $res->getCode() != 200
            ? '上传失败:' . $res->getMsg()
            : (string)($res->getData()['fileid'] ?? '');
        $response = $this->response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * 删除传图组件指定图片
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function webuploadDel(): ResponseInterface
    {
        $id = $this->input('id');
        $bigfile_info = $this->session()->get('bigfile_info', []);
        if (!isset($bigfile_info[$id])) {
            $response = $this->response->withStatus(404);
            $response->getBody()->write('No data');
            return $response;
        }
        $this->uploader->uploadDel($bigfile_info[$id]);
        unset($bigfile_info[$id]);
        $this->session()->set('bigfile_info', $bigfile_info);
        return $this->json($this->output->withCode(200));
    }

    /**
     * 传图组件缩略图显示
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function webuploadThumbnail(): ResponseInterface
    {
        $id = $this->input('id');
        if (empty($id)) {
            $response = $this->response->withStatus(400);
            $response->getBody()->write('No ID');
            return $response;
        }
        $bigfile_info = $this->session()->get('bigfile_info', []);
        if (!isset($bigfile_info[$id])) {
            return $this->response->withStatus(404);
        }
        $url = $bigfile_info[$id];
        $thumbnail = $this->uploader->copyImage($url, 120, 120);
        $imagevariable = file_get_contents(CSPUBLIC . str_replace($this->config['basehost'], '', $thumbnail));
        $response = $this->response
            ->withHeader('Content-Type', 'image/jpeg')
            ->withHeader('Content-Length', (string)strlen($imagevariable));
        $response->getBody()->write($imagevariable);
        return $response;
    }

    /**
     * 删除图集中的某张图片
     * @return ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function webuploadImageDel(): ResponseInterface
    {
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $field = $this->inputString('field');
        $pic = $this->inputString('pic');
        $res = $this->fileService->imgsDel($fid, $id, $field, $pic);
        // AJAX 操作端点，无对应模板，强制 JSON 输出
        return $this->json($res);
    }

    /**
     * 超大附件上传
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function superFileUpload(): ResponseInterface
    {
        if ($r = $this->checkAllow()) {
            return $r;
        }
        $file = $this->request->getUploadedFiles()['file'] ?? null;
        $index = $this->inputInt('index');
        $filename = $this->inputString('filename');
        $res = $this->uploader->superFileUpload($file, $index, $filename, 'superFile');
        return $this->json($res);
    }

    /**
     * 删除附件图片
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function delImg(): ResponseInterface
    {
        if ($r = $this->checkAllow()) {
            return $r;
        }
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $identifier = $this->inputString('identifier');
        $res = $this->fileService->delImg($fid, $id, $identifier);
        // AJAX 操作端点，无对应模板，强制 JSON 输出
        return $this->json($res);
    }

    /**
     * 设置封面
     * @return ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function webuploadCover(): ResponseInterface
    {
        if ($r = $this->checkAllow()) {
            return $r;
        }
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $pic = $this->inputString('pic');
        $res = $this->fileService->webuploadCover($fid, $id, $pic);
        // AJAX 操作端点，无对应模板，强制 JSON 输出
        return $this->json($res);
    }

    /**
     * 删除图集中的某张图片
     * @return ResponseInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SlimCMS\Error\TextException
     */
    public function delFromAddons(): ResponseInterface
    {
        if ($r = $this->checkAllow()) {
            return $r;
        }
        $fid = $this->inputInt('fid');
        $id = $this->inputInt('id');
        $identifier = $this->inputString('identifier');
        $url = $this->inputString('url');
        $res = $this->fileService->delFromAddons($fid, $id, $identifier, $url);
        // AJAX 操作端点，无对应模板，强制 JSON 输出
        return $this->json($res);
    }
}
