<?php
declare(strict_types=1);

namespace app\Table;

use Slim\App;
use SlimCMS\Core\Form\TableHookInterface;
use SlimCMS\Core\Redis;
use SlimCMS\Core\Table;
use SlimCMS\Core\Request;
use SlimCMS\Core\Ueditor;

class SysconfigTable extends Table implements TableHookInterface
{
    use \SlimCMS\Traits\Table;

    private Request $req;
    private Ueditor $ueditor;

    public function __construct(App $app, Redis $redis, Ueditor $ueditor, Request $req)
    {
        parent::__construct($app, $redis);
        $this->req = $req;
        $this->ueditor = $ueditor;
    }

    /**
     * 数据获取之后的自定义处理
     */
    public function dataViewAfter(array &$data, array $options): int|array
    {
        if (!empty($data['value'])) {
            $data['value'] = stripslashes($data['value']);
        }
        return 200;
    }

    /**
     * 表单HTML获取之前的自定义处理
     */
    public function getFormHtmlBefore(array &$fields, array &$row, array $form, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            $value = aval($row, 'value', '');
            $row['ueditorHtml'] = $this->ueditor->setRequest($this->request)->ueditor('ueditorValue', $value, ['identity' => 'admin']);
        }
        return 200;
    }

    /**
     * 数据保存前的自定义处理
     */
    public function dataSaveBefore(array &$data, array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            if ($data['type'] == 5) {
                $data['value'] = $this->req->input('ueditorValue', 'htmltext');
            }
            if ($data['type'] == 4) {
                $data['value'] = $this->req->input('value', 'addon');
            }
        }
        return 200;
    }

    /**
     * 数据保存后的自定义处理
     */
    public function dataSaveAfter(array $data, array $row, array $options): int|array
    {
        if ($this->request->getAttribute('adminContext') === true) {
            $cfg = CSDATA . '/ConfigCache.php';
            if (!is_writeable($cfg)) {
                return 21020;
            }
            $str = "<?php\r\nreturn ";
            $row = $this->t('sysconfig')->fetchList();
            $arr = [];
            foreach ($row as $v) {
                $value = str_replace("'", '', $v['value']);
                $arr[$v['varname']] = $value;
            }
            $arr = ['cfg' => $arr];
            $str .= var_export($arr, true) . ';';
            file_put_contents($cfg, $str);
            is_file(CSDATA . 'CompiledContainer.php') && unlink(CSDATA . 'CompiledContainer.php');
        }
        return 200;
    }
}
