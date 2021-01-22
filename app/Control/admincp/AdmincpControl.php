<?php

/**
 * 后台控制类
 */
declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Forms;
use App\Model\admincp\LoginModel;
use SlimCMS\Error\TextException;
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
            $url = self::url('?p=login&referer=' . urlencode(self::url()));
            if (self::$response->determineContentType() == 'application/json') {
                $res = self::$output->withCode(24071)->withReferer($url)->jsonSerialize();
                echo json_encode($res);
            } else {
                header('location:' . $url);
            }
            exit();
        }
    }

    /**
     * 权限检测
     * @param $auth
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    protected function checkAllow(string $auth = null)
    {
        $auth = $auth ?: $this->p;
        $res = LoginModel::checkAllow(self::$admin, $auth);
        if ($res->getCode() != 200) {
            $this->directTo($res);
            $url = $res->getReferer() ?: self::url('?p=main/index');
            if (self::$response->determineContentType() == 'application/json') {
                $res = self::$output->withCode(21048)->withReferer('')->jsonSerialize();
                echo json_encode($res);
            }else{
                header('location:' . $url);
            }
            exit;
        }
        LoginModel::logSave(self::$admin);
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