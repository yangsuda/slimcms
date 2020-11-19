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

class LoginControl extends ControlAbstract
{
    public function login()
    {
        $formhash = self::input('formhash');
        if($formhash){
            $ccode = (string)self::input('ccode');
            $img = new \Securimage();
            if (!$img->check($ccode)) {
                $output = self::$output->withCode(24023);
                return $this->response($output);
            }
            $res = Forms::submitCheck($formhash);
            if($res->getCode()!=200){
                return $this->response($res);
            }
            $userid = self::input('userid');
            $pwd = self::input('pwd');
            $referer = self::input('referer', 'url');
            $res = LoginModel::loginCheck($userid, $pwd, $referer);
            return $this->response($res);
        }
        return $this->view();
    }

    /**
     * 退出
     * @return array
     */
    function out()
    {
        self::$request->getCookie()->set('adminauth');
        $referer = self::url('?p=login&referer=' . urlencode(self::$config['referer']));
        $output = self::$output->withCode(200,21047)->withReferer($referer);
        return $this->response($output);
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
        $img->num_lines   = 0;
        $img->noise_level = 1;
        return $img->show();
    }

}