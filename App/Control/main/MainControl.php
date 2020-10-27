<?php

/**
 * 后台控制类
 */

namespace App\Control\main;

use Exception;
use Slim\Exception\HttpInternalServerErrorException;
use SlimCMS\Core\Control;
use SlimCMS\Error\TextException;

class MainControl extends Control
{
    public function test()
    {
        throw new TextException(21051);
        $this->request->input('p');
        return $this->response->output(21007);
        var_dump($this->request->input('p'));exit;
    }
}