<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;
use SlimCMS\Core\Request;

class AdminlogTable extends Table
{
    //根据自己情况决定是否开启分表，如果需要开启，取消注释
    /*public function __construct(Request $request, string $tableName, string $extendName = null)
    {
        //根据年份进行分表
        if (!isset($extendName)) {
            $extendName = date('Y');
            $this->subtable($tableName, $extendName);
        }
        parent::__construct($request, $tableName, $extendName);
    }*/
}
