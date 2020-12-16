<?php
declare(strict_types=1);

namespace App\Table;

use App\Core\Table;

class SysconfigTable extends Table
{
    /**
     * 数据获取之后的自定义处理
     * @param $data
     * @return array
     */
    public function dataViewAfter(&$data): int
    {
        if (!empty($data['value'])) {
            $data['value'] = stripslashes($data['value']);
        }
        return 200;
    }

    /**
     * 表单HTML获取之前的自定义处理
     * @param $data
     * @return array
     */
    public function getFormHtmlBefore(&$fields, &$data, &$form): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            $value = aval($data, 'value', '');
            $data['ueditorHtml'] = ueditor('ueditorValue', $value, ['identity' => 'admin']);
        }
        return 200;
    }

    /**
     * 数据保存前的自定义处理
     * @param $data
     * @return array
     */
    public function dataSaveBefore(&$data, $row = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
            if ($data['type'] == 5) {
                $data['value'] = self::input('ueditorValue', 'htmltext');
            }
            if ($data['type'] == 4) {
                $data['value'] = self::input('value', 'addon');
            }
        }
        return 200;
    }

    /**
     * 数据保存后的自定义处理
     * @param $data
     * @return array
     */
    public function dataSaveAfter($data, $row = []): int
    {
        if (defined('MANAGE') && MANAGE == 1) {
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
        }
        return 200;
    }
}