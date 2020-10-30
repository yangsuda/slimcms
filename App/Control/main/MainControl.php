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
        $this->input('p');
        return $this->view();
        var_dump($this->input('p'));
        exit;
    }
}