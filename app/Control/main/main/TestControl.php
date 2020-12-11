<?php

/**
 * 后台控制类
 */
declare(strict_types=1);

namespace App\Control\main\main;

use App\Core\Forms;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Core\Cookie;
use SlimCMS\Core\Ueditor;
use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\Http;
use SlimCMS\Helper\Str;

class TestControl extends ControlAbstract
{

    public function test()
    {
        $row = self::input('aa');
        echo ($row);
        exit;
    }
}