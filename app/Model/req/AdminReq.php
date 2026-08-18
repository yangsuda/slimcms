<?php

declare(strict_types=1);

namespace app\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class AdminReq extends ReqAbstract
{
    protected function groupid(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function status(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function userid(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }
}
