<?php
/**
 * 默认控制类
 */

namespace App\Control\main;

use SlimCMS\Abstracts\ControlAbstract;

class DefaultControl extends ControlAbstract
{
    /**
     * 默认首页
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function index()
    {
        return $this->view(self::$output,'index');
    }
}