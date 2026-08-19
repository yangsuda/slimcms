<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\SysconfigEntity;
use SlimCMS\Abstracts\RepositoryAbstract;

class SysconfigRepository extends RepositoryAbstract
{
    public function fetch(string $field, int $cacheTime = 0): ?SysconfigEntity
    {
        $data = parent::fetch($field, $cacheTime);
        return $data ? SysconfigEntity::fromArray($data) : null;
    }

    public function fetchList(string $field, string $indexField = '', int $cacheTime = 0): array
    {
        $data = parent::fetchList($field, $indexField, $cacheTime);
        return SysconfigEntity::fromArrayList($data);
    }

    public function list(string $fields = 'id,createtime', int $page = 1, int $pagesize = 30): array
    {
        $data = parent::list($fields, $page, $pagesize);
        return SysconfigEntity::fromArrayList($data);
    }
}
