<?php

declare(strict_types=1);

namespace App\Model\resp;

use App\Service\table\AdmingroupService;
use SlimCMS\Abstracts\RespAbstract;
use SlimCMS\Abstracts\TableAbstract;

class AdminResp extends RespAbstract
{
    protected function groupid(array &$data, TableAbstract $table): void
    {
        $field = __FUNCTION__;
        !empty($data[$field]) && $data['_' . $field] = $this->relations[$field][$data[$field]] ?? [];
    }

    protected function groupidRelation(array $data, TableAbstract $table): array
    {
        $field = str_replace('Relation','',__FUNCTION__);
        return AdmingroupService::instance()
            ->withWhere(['ids' => array_column($data, $field)])
            ->fetchList('id,groupname,purviews', 'id');
    }
}
