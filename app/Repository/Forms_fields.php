<?php
declare(strict_types=1);

namespace App\Repository;


use SlimCMS\Abstracts\RepositoryAbstract;

class Forms_fields extends RepositoryAbstract
{
    protected static function condition(array $param): array
    {
        $where = parent::condition($param);
        !empty($param['formid']) && $where['formid'] = $param['formid'];
        !empty($param['datatype']) && $where['datatype'] = $param['datatype'];
        !empty($param['available']) && $where['available'] = $param['available'];
        !empty($param['infront']) && $where['infront'] = $param['infront'];
        !empty($param['unique']) && $where['unique'] = $param['unique'];
        !empty($param['search']) && $where['search'] = $param['search'];
        !empty($param['orderby']) && $where['orderby'] = $param['orderby'];
        !empty($param['inlist']) && $where['inlist'] = $param['inlist'];
        !empty($param['inlistcp']) && $where['inlistcp'] = $param['inlistcp'];
        !empty($param['forbidedit']) && $where['forbidedit'] = $param['forbidedit'];
        !empty($param['precisesearch']) && $where['precisesearch'] = $param['precisesearch'];
        !empty($param['defaultorder']) && $where['defaultorder'] = $param['defaultorder'];
        !empty($param['isexport']) && $where['isexport'] = $param['isexport'];
        return $where;
    }
}
