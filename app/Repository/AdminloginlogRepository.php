<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\AdminloginlogEntity;
use SlimCMS\Abstracts\RepositoryAbstract;

class AdminloginlogRepository extends RepositoryAbstract
{
    public function fetch(string $field, int $cacheTime = 0): ?AdminloginlogEntity
    {
        $data = parent::fetch($field, $cacheTime);
        return $data ? AdminloginlogEntity::fromArray($data) : null;
    }

    public function fetchList(string $field, string $indexField = '', int $cacheTime = 0): array
    {
        $data = parent::fetchList($field, $indexField, $cacheTime);
        return AdminloginlogEntity::fromArrayList($data);
    }

    public function list(string $fields = 'id,createtime', int $page = 1, int $pagesize = 30): array
    {
        $data = parent::list($fields, $page, $pagesize);
        $data['list'] = AdminloginlogEntity::fromArrayList($data['list']);
        return $data;
    }
}
