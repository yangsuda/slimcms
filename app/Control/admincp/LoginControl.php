<?php
/**
 * 后台登录控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Csrf;
use App\Core\Forms;
use App\Service\admincp\AuthService;
use SlimCMS\Helper\ImageCode;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\Crypt;

class LoginControl extends ControlAbstract
{
    public function login()
    {
        $formhash = self::input('formhash');
        if ($formhash) {
            $ccode = self::inputString('ccode');
            if (ImageCode::checkCode($ccode) === false) {
                $output = self::$output->withCode(24023);
                return $this->response($output);
            }
            $res = Forms::submitCheck($formhash);
            if ($res->getCode() != 200) {
                return $this->response($res);
            }
            $userid = self::input('userid');
            $pwd = self::input('pwd');
            $res = AuthService::instance()->loginCheck($userid, $pwd);
            if ($res->getCode() == 200) {
                isset($_SESSION) ? '' : session_start();
                $_SESSION['adminAuth'] = Crypt::encrypt($res->getData()['id']);
                $referer = self::input('referer', 'url');
                $referer = $referer ?: '?p=main/index';
                $res = $res->withReferer($referer);
            }
            return $this->response($res);
        }

        isset($_SESSION) ? '' : session_start();
        $adminAuth = (string)aval($_SESSION, 'adminAuth');
        $auth = Crypt::decrypt($adminAuth);
        if (is_numeric($auth)) {
            self::directTo(self::$output->withReferer('?p=main/index'));
        }

        //防止安装完后点登录，成功后又退回安装页面
        $referer = self::input('referer', 'url');
        $referer = $referer ?: self::$output->getReferer();
        if (preg_match('/(install\/index.php)$/', $referer)) {
            $referer = '';
        }
        $res = self::$output->withCode(200)->withData(['referer' => $referer,'csrfToken'=>Csrf::getToken()]);
        return $this->view($res);
    }

    /**
     * 退出
     * @return array
     */
    public function logout()
    {
        isset($_SESSION) ? '' : session_start();
        unset($_SESSION['adminAuth']);
        $referer = '?p=login&referer=' . urlencode(self::$config['referer']);
        $output = self::$output->withCode(200, 21047)->withReferer($referer);
        return self::directTo($output);
    }

    /**
     * 验证码生成
     */
    public function captcha()
    {
        ImageCode::doimg();
    }

    /**
     * 获取FORMHASH
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function formHash()
    {
        $data = [];
        $data['formHash'] = self::$request->getFormHash();
        $output = self::$output->withData($data);
        return self::response($output);
    }
}
