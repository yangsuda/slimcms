<?php
/**
 * 后台登陆控制类
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Forms;
use App\Model\admincp\LoginModel;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\Crypt;

class LoginControl extends ControlAbstract
{
    public function login()
    {
        $formhash = self::input('formhash');
        if ($formhash) {
            $ccode = self::inputString('ccode');
            $img = new \Securimage();
            if (!$img->check($ccode)) {
                $output = self::$output->withCode(24023);
                return $this->response($output);
            }
            $res = Forms::submitCheck($formhash);
            if ($res->getCode() != 200) {
                return $this->response($res);
            }
            $userid = self::input('userid');
            $pwd = self::input('pwd');
            $res = LoginModel::loginCheck($userid, $pwd);
            if ($res->getCode() == 200) {
                isset($_SESSION) ? '' : session_start();
                $_SESSION['adminAuth'] = Crypt::encrypt($res->getData()['id']);
                $referer = self::input('referer', 'url');
                $referer = $referer ?: self::url('?p=main/index');
                $res = $res->withReferer($referer);
            }
            return $this->response($res);
        }

        isset($_SESSION) ? '' : session_start();
        $adminAuth = (string)aval($_SESSION, 'adminAuth');
        $auth = Crypt::decrypt($adminAuth);
        if (is_numeric($auth)) {
            self::directTo(self::$output->withReferer(self::url('?p=main/index')));
        }

        //防止安装完后点登陆，成功后又退回安装页面
        $referer = self::input('referer', 'url');
        $referer = $referer ?: self::$output->getReferer();
        if (preg_match('/(install\/index.php)$/', $referer)) {
            self::$output = self::$output->withReferer('');
        }
        $res = self::$output->withCode(200)->withData(['referer'=>$referer]);
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
        $referer = self::url('?p=login&referer=' . urlencode(self::$config['referer']));
        $output = self::$output->withCode(200, 21047)->withReferer($referer);
        return self::directTo($output);
    }

    /**
     * 验证码生成
     */
    public function captcha()
    {
        $img = new \Securimage();
        $img->code_length = 4;
        $img->image_width = 80;
        $img->image_height = 40;
        $img->ttf_file = CSDATA . 'fonts/INDUBITA.TTF';
        $img->text_color = new \Securimage_Color('#009D41');
        $img->charset = '0123456789';
        $img->num_lines = 0;
        $img->noise_level = 1;
        return $img->show();
    }

}