<?php
declare(strict_types=1);

namespace App\Repository;


use SlimCMS\Abstracts\RepositoryAbstract;

class Forms extends RepositoryAbstract
{
    protected static function condition(array $param): array
    {
        $where = parent::condition($param);
        !empty($param['export']) && $where['export'] = $param['export'];
        !empty($param['cpcheck']) && $where['cpcheck'] = $param['cpcheck'];
        !empty($param['isarchive']) && $where['isarchive'] = $param['isarchive'];
        !empty($param['cpdel']) && $where['cpdel'] = $param['cpdel'];
        !empty($param['cpadd']) && $where['cpadd'] = $param['cpadd'];
        return $where;
    }
}
