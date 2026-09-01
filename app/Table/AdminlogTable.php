<?php

declare(strict_types=1);

namespace app\Table;

use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Table;

class AdminlogTable extends Table implements TableHookInterface
{
    /**
     * 重写设置表名(根据自己情况决定是否开启分表，如果需要开启，取消注释)
     * @param string $tableName
     * @param string|null $extendName
     * @return self
     */
    public function setTableName(string $tableName, string $extendName = null): self
    {
        $tableName = strtolower(substr(pathinfo(__CLASS__, PATHINFO_FILENAME), 0, -5));
        //根据年份进行分表
        if (!isset($extendName)) {
            $extendName = date('Y');
        }
        return parent::setTableName($tableName, $extendName);
    }
}
