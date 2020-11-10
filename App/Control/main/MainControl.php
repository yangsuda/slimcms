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
        //$row = self::t('admin')->withWhere(2)->fetch();
        //var_dump($row);
        //exit;
        self::input('p');
        $output = self::$output->withCode(22004, ['title' => 'aa']);
        //return $this->response($output);
        return $this->view($output);
    }
}