<?php

declare(strict_types=1);

namespace app\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class FormsReq extends ReqAbstract
{
    protected function export(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function cpcheck(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function cpadd(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function cpdel(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function isarchive(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function table(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }
}
