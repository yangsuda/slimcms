<?php

/**
 * 管理员
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Repository;


use SlimCMS\Abstracts\RepositoryAbstract;

class Admin extends RepositoryAbstract
{
    protected static function condition(array $param): array
    {
        $where = parent::condition($param);
        !empty($param['status']) && $where['status'] = $param['status'];
        !empty($param['groupid']) && $where['groupid'] = explode(',', (string)$param['groupid']);
        return $where;
    }
}
