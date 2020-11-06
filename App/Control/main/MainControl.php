<?php

/**
 * 后台控制类
 */

namespace App\Control\main;

use SlimCMS\Abstracts\ControlAbstract;

class MainControl extends ControlAbstract
{
    public function test()
    {
        $row = $this->t('admin')->withWhere(1)->fetch();
        var_dump($row);
        exit;
        $this->input('p');
        $output = $this->output->withCode(21012)->withReferer('http://www.cs090.com');
        //return $this->response($output);
        return $this->view($output);
    }
}