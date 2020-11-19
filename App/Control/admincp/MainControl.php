<?php
/**
 * 后台首页控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

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
        $id = self::input('id', 'int');
        return MainModel::recovery($id);
    }

    /**
     * 文件校验
     * @return array
     */
    public function fileVerify()
    {
        return MainModel::fileVerify();
    }

    /**
     * 更新文件校验KEY
     * @return array
     */
    public function updateVerifyKey()
    {
        $file = self::input('file');
        return MainModel::updateVerifyKey($file);
    }

    /**
     * 删除附件图片
     * @return array
     */
    public function delImg()
    {
        $param = self::input(['did' => 'int', 'id' => 'int', 'identifier' => 'string']);
        return MainModel::delImg($param);
    }

    /**
     * 删除奖品设置
     * @return array
     */
    public function prizeDel()
    {
        $prizeid = self::input('prizeid', 'int');
        return LotteryModel::prizeDel($prizeid);
    }
}