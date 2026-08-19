<?php

declare(strict_types=1);

namespace app\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class SysconfigReq extends ReqAbstract
{
    protected function varname(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function groupid(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function type(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }
}
