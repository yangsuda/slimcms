<?php

declare(strict_types=1);

namespace App\Table;

use App\Core\Table;
use App\Model\admincp\ApimanageModel;

class ApilistTable extends Table
{
    /**
     * 数据保存前的自定义处理
     * @param $data
     * @param array $row
     * @return int
     */
    public function dataSaveBefore(&$data, $row = [], $options = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            !empty($data['result']) && $data['result'] = self::$request->htmlspecialchars($data['result'],'de');
            $data['identifier'] = ApimanageModel::getIdentifier($data['path'], aval($data,'formid'), aval($data,'openapiid'))->getData()['identifier'];
        }
        return 200;
    }
}
