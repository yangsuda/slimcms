<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;
use SlimCMS\Helper\Str;

class AppsTable extends Table
{
    /**
     * 表单HTML获取之前的自定义处理
     * @param $fields
     * @param $data
     * @param $form
     * @return int
     * @throws \SlimCMS\Error\TextException
     */
    public function getFormHtmlBefore(&$fields, &$data, &$form, &$options): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            empty($data['appid']) && $data['appid'] = Str::random(10);
            empty($data['appsecret']) && $data['appsecret'] = Str::random(20);
        }
        return 200;
    }
}
