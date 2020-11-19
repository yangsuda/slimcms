<?php
declare(strict_types=1);

namespace App\Table;

use App\Core\Table;

class SysconfigTable extends Table
{

    /**
     * 数据保存后的自定义处理
     * @param $data
     * @return array
     */
    public function dataSaveAfter($data, $row = []): int
    {
        $cfg = CSDATA . '/configCache.php';
        if (!is_writeable($cfg)) {
            return 21020;
        }
        $str = "<?php\r\nreturn ";
        $row = self::t('sysconfig')->fetchList();
        $arr = [];
        foreach ($row as $v) {
            $value = str_replace("'", '', $v['value']);
            if ($v['type'] == 'number') {
                $value = (int)$v['value'];
            } elseif ($v['type'] == 'serialize') {
                $value = Str::serializeData($v['value']);
            }
            $arr[$v['varname']] = $value;
        }
        $arr = ['cfg' => $arr];
        $str .= var_export($arr, true) . ';';
        file_put_contents($cfg, $str);
        return 200;
    }
}
