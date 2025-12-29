<?php

declare(strict_types=1);

namespace App\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class FormsReq extends ReqAbstract
{
    protected function export(array $param, $words = null): void
    {
        isset($words) && $this->where['export'] = $words;
    }

    protected function cpcheck(array $param, $words = null): void
    {
        isset($words) && $this->where['cpcheck'] = $words;
    }

    protected function cpadd(array $param, $words = null): void
    {
        isset($words) && $this->where['cpadd'] = $words;
    }

    protected function cpdel(array $param, $words = null): void
    {
        isset($words) && $this->where['cpdel'] = $words;
    }

    protected function isarchive(array $param, $words = null): void
    {
        isset($words) && $this->where['isarchive'] = $words;
    }
}
