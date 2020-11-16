<?php

/**
 * 后台控制类
 */

namespace App\Control\main;

use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Core\Ueditor;
use SlimCMS\Helper\Http;

class MainControl extends ControlAbstract
{
    public function test()
    {
        //$row = self::t('admin')->withWhere(2)->fetch();
        //var_dump($row);
        //exit;
        /*$post = [];

        $post['test'] = new UploadedFile(
        'E:\cs090\slimCMS\public\22.xls',
        'aaa1.jpg',
        'image/jpeg'
    );

        $a = self::$request->getRequest()->withUploadedFiles($post)->getUploadedFiles();
        var_dump($a['test']->moveTo(CSPUBLIC.'33.xls'));exit;*/
        $b = Http::currentUrl();
        var_dump(self::currentUrl('&p=forms/dataList&id='),$b);exit;
        $p = self::input('p');
        $output = self::$output->withCode(22004, ['title' => 'aa']);
        //return $this->response($output)
        $a = Ueditor::config();
        var_dump($a);exit;
        //$content1 = self::$output->withData(['identifier'=>'bb1','default'=>'bb'])->withTemplate('block/fieldshtml/text')->analysisTemplate(true);
        //var_dump($content,$content1);exit;
        return $this->view($output);
    }
}