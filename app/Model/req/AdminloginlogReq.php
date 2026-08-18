<?php

declare(strict_types=1);

namespace app\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class AdminloginlogReq extends ReqAbstract
{
    protected function userid(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }
}
