<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Controller\admin;

use App\Core\Csrf;
use App\Core\Forms;
use App\Service\admin\AuthService;
use App\Service\admin\MainService;
use Psr\Http\Message\ResponseInterface;

class MainController extends AdminController
{
    /**
     * 后台仪表盘
     * 当前管理员信息通过 AdminAuthMiddleware 注入的 request attribute 'admin' 获取
     */
    public function index(): ResponseInterface
    {
        $apiName = substr(md5(self::$setting['security']['authkey']), -8);
        self::$output = self::$output->withData(['apiName' => $apiName]);
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
        $res = MainService::recovery($id);
        return self::response($res);
    }

    /**
     * 修改密码
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function updatePwd()
    {
        $this->checkAllow();
        if (self::$request->getRequest()->getMethod() === 'POST') {
            $formhash = self::input('formhash');
            $res = Forms::submitCheck($formhash);
            if ($res->getCode() != 200) {
                return $this->directTo($res);
            }
            $oldpwd = self::inputString('oldpwd');
            $newpwd = self::inputString('newpwd');
            $res = AuthService::instance()->updatePwd(self::$admin['userid'], $oldpwd, $newpwd);
            return $this->directTo($res);
        }
        self::$output = self::$output->withData(['csrfToken' => Csrf::getToken()]);
        return $this->view(self::$output);
    }

    /**
     * 多附件删除
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function delFromAddons()
    {
        $this->checkAllow();
        $param = self::input(['fid' => 'int', 'id' => 'int', 'identifier' => 'string', 'url' => 'string']);
        $res = MainService::delFromAddons($param);
        return self::response($res);
    }
}
