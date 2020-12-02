<?php

/**
 * 后台控制类
 */
declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Forms;
use App\Model\admincp\LoginModel;
use SlimCMS\Helper\Crypt;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Core\Request;
use SlimCMS\Core\Response;
use SlimCMS\Interfaces\OutputInterface;

class AdmincpControl extends ControlAbstract
{
    protected static $admin = [];

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        if (empty(self::$admin)) {
            isset($_SESSION) ? '' : session_start();
            $adminAuth = (string)aval($_SESSION, 'adminAuth');
            $auth = Crypt::decrypt($adminAuth);
            if (is_numeric($auth)) {
                $res = LoginModel::loginInfo((int)$auth);
                if ($res->getCode() != 200) {
                    return $this->directTo($res);
                }
                self::$admin = $res->getData()['admin'];
                self::$admin['adminAuth'] = $adminAuth;
            }
        }
        if (empty(self::$admin['id'])) {
            header('location:' . self::url('?p=login&referer=' . urlencode(self::url())));
            exit();
        }
        //检查权限许可
        $arr = ['main/index', 'forms/dataList', 'forms/dataSave', 'forms/dataCheck', 'forms/dataDel', 'forms/dataExport'];
        $p = self::input('p');
        !in_array($p, $arr) && $this->checkAllow($p);
        LoginModel::logSave(self::$admin);
    }

    protected function checkAllow($auth)
    {
        $res = LoginModel::checkAllow(self::$admin, $auth);
        if ($res->getCode() != 200) {
            return $this->directTo($res);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function view(OutputInterface $output = null, string $template = '')
    {
        $output = $output ?? self::$output;
        $output = $output->withData(['admin' => self::$admin, 'leftMenu' => $this->leftMenu()]);
        return parent::view($output, $template);
    }

    private function leftMenu()
    {
        $purviews = explode(',', aval(self::$admin, '_groupid/purviews'));
        $param = ['fid' => 1, 'ischeck' => 1, 'pagesize' => 200, 'inlistField' => 'inlistcp', 'cacheTime' => 600, 'order' => 'weight', 'noinput' => 1];
        $res = Forms::dataList($param)->getData();
        $arr = [];
        $weight = [];
        foreach ($res['list'] as $v) {
            if (!in_array('admin_AllowAll', $purviews) && !in_array('dataList' . $v['id'], $purviews)) {
                continue;
            }
            if (!empty($v['types'])) {
                $weight[$v['types']][] = $v['weight'];
                $arr[$v['types']]['types'] = ['key' => $v['types'], 'name' => $v['_types']];
                $arr[$v['types']]['subMenu'][] = $v;
            }
        }
        foreach ($arr as $k => $v) {
            array_multisort($weight[$k], SORT_DESC, $arr[$k]['subMenu']);
        }
        return $arr;
    }
}