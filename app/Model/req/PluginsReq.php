<?php

declare(strict_types=1);

namespace app\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class PluginsReq extends ReqAbstract
{
    protected function identifier(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }
}
