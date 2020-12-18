<?php

declare(strict_types=1);

namespace App\Control\main;

use App\Core\Forms;
use SlimCMS\Abstracts\ControlAbstract;

class MainControl extends ControlAbstract
{
    /**
     * 联动菜单数据
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function enumsData()
    {
        $egroup = self::input('egroup');
        $res = Forms::enumsData($egroup);
        return self::response($res);
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