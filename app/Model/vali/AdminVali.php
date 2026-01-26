<?php

namespace App\Model\vali;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;
use SlimCMS\Abstracts\ValiAbstract;

class AdminVali extends ValiAbstract
{

    public static function userid(): Validatable
    {
        $name = self::getFieldText(__FUNCTION__);
        //return v::noWhitespace()->setName($name)->setTemplate('{{name}} 不能包含空格');
        return v::allOf(
            v::noWhitespace()->setName($name)->setTemplate('{{name}} 不能包含空格'),
            v::length(6, 20)->setName($name)->setTemplate('{{name}} 长度必须在 {{minValue}} 到 {{maxValue}} 个字符之间')
        );
    }
}
