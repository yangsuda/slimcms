<?php

declare(strict_types=1);

namespace App\Model\resp;

use App\Service\table\AdmingroupService;
use App\Service\table\AdminService;
use SlimCMS\Abstracts\RespAbstract;

class AdminResp extends RespAbstract
{
    protected function groupid(array &$data, AdminService $table): void
    {
        $field = __FUNCTION__;
        !empty($data[$field]) && $data['_' . $field] = $this->relations[$field][$data[$field]] ?? [];
    }

    protected function groupidRelation(array $data, AdminService $table): array
    {
        $field = str_replace('Relation','',__FUNCTION__);
        return AdmingroupService::instance()
            ->withWhere(['ids' => array_column($data, $field)])
            ->fetchList('id,groupname,purviews', 'id');
    }
}
