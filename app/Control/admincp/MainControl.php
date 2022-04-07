<?php
/**
 * 后台首页控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Model\admincp\MainModel;
use SlimCMS\Interfaces\UploadInterface;

class MainControl extends AdmincpControl
{
    /**
     * 后台首页
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function index()
    {
        $apiName = substr(md5(self::$setting['security']['authkey']), -8);
        self::$output = self::$output->withData(['apiName'=>$apiName]);
        return $this->view(self::$output);
    }

    /**
     * 恢复数据
     * @return array
     */
    public function recovery()
    {
        $this->checkAllow();
        $id = self::inputInt('id');
        $res = MainModel::recovery($id);
        return self::response($res);
    }

    /**
     * 删除附件图片
     * @return array
     */
    public function delImg()
    {
        $this->checkAllow();
        $param = self::input(['fid' => 'int', 'id' => 'int', 'identifier' => 'string']);
        $res = MainModel::delImg($param);
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
        $file = aval($_FILES, 'file');
        $index = self::inputInt('index');
        $filename = self::inputString('filename');
        $upload = self::$container->get(UploadInterface::class);
        $res = $upload->superFileUpload($file, $index, $filename);
        return $this->json($res);
    }
}
