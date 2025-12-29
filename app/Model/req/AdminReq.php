<?php

declare(strict_types=1);

namespace App\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class AdminReq extends ReqAbstract
{
    protected function groupid(array $param, $words = null): void
    {
        isset($words) && $this->where['groupid'] = $words;
    }

    protected function status(array $param, $words = null): void
    {
        isset($words) && $this->where['status'] = $words;
    }

    protected function userid(array $param, $words = null): void
    {
        isset($words) && $this->where[] = self::t()->field('userid', $words, 'like');
    }
}
