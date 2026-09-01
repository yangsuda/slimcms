<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\AdmingroupEntity;
use SlimCMS\Abstracts\RepositoryAbstract;

class AdmingroupRepository extends RepositoryAbstract
{
    protected ?string $entityClass = AdmingroupEntity::class;
}
