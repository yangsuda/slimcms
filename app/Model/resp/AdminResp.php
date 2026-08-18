<?php

declare(strict_types=1);

namespace app\Model\resp;

use app\Repository\AdmingroupRepository;
use app\Repository\AdminRepository;
use SlimCMS\Abstracts\RespAbstract;

class AdminResp extends RespAbstract
{
    protected function _groupid(array &$list, AdminRepository $table)
    {
        $field = __FUNCTION__;
        $groupids = $this->r(AdmingroupRepository::class)
            ->withWhere(['ids' => array_column($list, 'groupid')])
            ->fetchList('id,groupname,purviews', 'id');
        foreach ($list as &$v) {
            !empty($v['groupid']) && $v[$field] = $groupids[$v['groupid']] ?? null;
        }
    }
}
