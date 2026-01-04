<?php

declare(strict_types=1);

namespace App\Model\resp;

use App\Service\table\AdmingroupService;
use SlimCMS\Abstracts\RespAbstract;

class AdminResp extends RespAbstract
{
    protected function _groupid(array &$data): void
    {
        !empty($data['groupid']) && $data[__FUNCTION__] = AdmingroupService::instance()->withWhere(['ids' => $data['groupid']])->fetch('id,groupname,purviews');
    }
}
