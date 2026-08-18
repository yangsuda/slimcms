<?php

namespace app\Model\vali;

use Respect\Validation\Validatable;
use Respect\Validation\Validator as v;
use SlimCMS\Abstracts\ValiAbstract;

class AdminVali extends ValiAbstract
{

    public function userid(): Validatable
    {
        //return v::noWhitespace()->setName($name)->setTemplate('{{name}} 不能包含空格');
        return v::allOf(
            v::noWhitespace()->setName('用户名')->setTemplate('{{name}} 不能包含空格'),
            v::length(5, 20)->setName('用户名')->setTemplate('{{name}} 长度必须在 {{minValue}} 到 {{maxValue}} 个字符之间')
        );
    }
}
