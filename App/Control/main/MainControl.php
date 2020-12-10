<?php

/**
 * 后台控制类
 */
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
}