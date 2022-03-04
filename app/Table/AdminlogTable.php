<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;
use SlimCMS\Core\Request;

class AdminlogTable extends Table
{
    //根据自己情况决定是否开启是否分表，如果需要开启，取消注释
    /*public function __construct(Request $request, string $tableName)
    {
        //根据年份进行分表
        $year = date('Y');
        $this->subtable($tableName, $year);

        parent::__construct($request, $tableName . $year);
    }*/
}
