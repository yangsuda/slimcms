<?php

declare(strict_types=1);

namespace app\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class SysenumReq extends ReqAbstract
{
    protected function egroup(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function reid(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function ischeck(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function evalue(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function evalueOverNil(array $param, $words = null): void
    {
        isset($words) && $this->where[] = 'evalue>0';
    }
}
