<?php
/**
 * 后台首页控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Model\admincp\MainModel;

class MainControl extends AdmincpControl
{
    /**
     * 后台首页
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function index()
    {
        return $this->view();
    }

    /**
     * 恢复数据
     * @return array
     */
    public function recovery()
    {
        $id = self::inputInt('id');
        $res = MainModel::recovery($id);
        return self::response($res);
    }

    /**
     * 文件校验
     * @return array
     */
    public function fileVerify()
    {
        $res = MainModel::fileVerify()->withReferer(self::url('?p=forms/dataList&fid=3'));
        return $this->directTo($res);
    }

    /**
     * 更新文件校验KEY
     * @return array
     */
    public function updateVerifyKey()
    {
        $file = self::inputString('file');
        $res = MainModel::updateVerifyKey($file)->withReferer(self::url('?p=forms/dataList&fid=3'));
        return $this->directTo($res);
    }

    /**
     * 删除附件图片
     * @return array
     */
    public function delImg()
    {
        $param = self::input(['fid' => 'int', 'id' => 'int', 'identifier' => 'string']);
        $res = MainModel::delImg($param);
        return self::response($res);
    }

    /**
     * 接口文档
     */
    public function apiIntro()
    {
        $res = MainModel::apiIntro();
        $template = '';
        if ($res->getCode() != 200) {
            $template = 'prompt';
        }
        $fileName = substr(md5(self::$setting['security']['authkey']), -8);
        $res = $res->withData(['fileName' => $fileName]);
        return $this->view($res, $template);
    }
}