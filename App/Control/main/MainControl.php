<?php

/**
 * 后台控制类
 */
declare(strict_types=1);

namespace App\Control\main;

use App\Core\Forms;
use Slim\Psr7\Stream;
use Slim\Psr7\UploadedFile;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Core\Cookie;
use SlimCMS\Core\Ueditor;
use SlimCMS\Helper\Http;

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

    public function test()
    {
        $row = self::$request->getRequest()->getQueryParams();
        var_dump($row);
        exit;
        /*$post = [];

        $post['test'] = new UploadedFile(
        'E:\cs090\slimCMS\public\22.xls',
        'aaa1.jpg',
        'image/jpeg'
    );

        $a = self::$request->getRequest()->withUploadedFiles($post)->getUploadedFiles();
        var_dump($a['test']->moveTo(CSPUBLIC.'33.xls'));exit;*/

        $p = self::input('p');
        var_dump(self::url('&p=forms/dataList&id='), $p);
        exit;

        $output = self::$output->withCode(22004, ['title' => 'aa']);
        //return $this->response($output)
        $a = Ueditor::config();
        var_dump($a);
        exit;
        //$content1 = self::$output->withData(['identifier'=>'bb1','default'=>'bb'])->withTemplate('block/fieldshtml/text')->analysisTemplate(true);
        //var_dump($content,$content1);exit;
        return $this->view($output);
    }
}