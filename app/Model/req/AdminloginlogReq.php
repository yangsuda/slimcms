<?php

declare(strict_types=1);

namespace App\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class AdminloginlogReq extends ReqAbstract
{
    protected function userid(array $param, $words = null): void
    {
        isset($words) && $this->where['userid'] = $words;
    }
}
