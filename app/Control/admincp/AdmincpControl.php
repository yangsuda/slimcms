<?php

/**
 * 后台控制类
 */
declare(strict_types=1);

namespace App\Control\admincp;

use App\Core\Forms;
use App\Service\admincp\AuthService;
use App\Service\table\AdminlogService;
use SlimCMS\Helper\Crypt;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Core\Request;
use SlimCMS\Core\Response;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Helper\Str;
use SlimCMS\Interfaces\OutputInterface;

class AdmincpControl extends ControlAbstract
{
    protected static $admin = [];

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        self::$config['rewriteUrl'] = false;//后台伪静态强制不开启
        self::$config['urlEncrypt'] = false;//后台URL加密不开启
        if (empty(self::$admin)) {
            isset($_SESSION) ? '' : session_start();
            $adminAuth = (string)aval($_SESSION, 'adminAuth');
            $auth = Crypt::decrypt($adminAuth);
            if (is_numeric($auth)) {
                $res = AuthService::instance()->loginInfo((int)$auth);
                if ($res->getCode() != 200) {
                    return $this->directTo($res);
                }
                self::$admin = $res->getData()['admin'];
                self::$admin['adminAuth'] = $adminAuth;
            }
        }
        if (empty(self::$admin['id'])) {
            $url = '?p=login&referer=' . urlencode(self::url());
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
        $res = AuthService::instance()->checkAllow(self::$admin, $auth);
        if ($res->getCode() != 200) {
            $this->directTo($res);
            $url = $res->getReferer() ?: '?p=main/index';
            if (self::$response->determineContentType() == 'application/json') {
                $res = self::$output->withCode(21048)->withReferer('')->jsonSerialize();
                echo json_encode($res);
            } else {
                header('location:' . $url);
            }
            exit;
        }
        //日志记录
        if (!empty(self::$config['adminLog'])) {
            $query = self::$request->getRequest()->getUri()->getQuery();
            $server = self::$request->getRequest()->getServerParams();
            $method = aval($server, 'REQUEST_METHOD');
            $query = substr($query, 0, 200);
            $postinfo = self::$request->getRequest()->getParsedBody();
            $postinfo = $postinfo ? serialize(Str::addslashes($postinfo)) : '';
            $postinfo = substr($postinfo, 0, 5000);
            $data = [
                'adminid' => aval(self::$admin, 'id'),
                'adminname' => aval(self::$admin, 'userid'),
                'method' => $method,
                'query' => $query,
                'ip' => Ipdata::getip(),
                'createtime' => TIMESTAMP,
                'postinfo' => $postinfo,
                'route' => self::input('p')
            ];
            AdminlogService::instance()->add($data);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function view(OutputInterface $output = null, string $template = '')
    {
        $output = $output ?? self::$output;
        $data = [];
        $data['leftMenu'] = $this->leftMenu();
        if (empty($output->getData()['admin'])) {
            $data['admin'] = self::$admin;
        }
        $output = $output->withData($data);
        return parent::view($output, $template);
    }

    private function leftMenu()
    {
        $purviews = aval(self::$admin, '_groupid/purviews');
        $purviews = $purviews ? explode(',', $purviews) : [];
        $param = ['fid' => 1, 'ischeck' => 1, 'pagesize' => 200, 'inlistField' => 'inlistcp', 'cacheTime' => 60, 'order' => 'weight', 'noinput' => 1];
        $res = Forms::dataList($param)->getData();
        $arr = [];
        $weight = [];
        foreach ($res['list'] as $v) {
            if (!empty($v['jumpurl']) && !preg_match('/^http/i', $v['jumpurl'])) {
                $urlinfo = parse_url($v['jumpurl']);
                parse_str($urlinfo['query'], $para);
                $purview = !empty($para['p']) ? $para['p'] : '';
            } else {
                $purview = 'dataList' . $v['id'];
            }
            if (!in_array('admin_AllowAll', $purviews) && !in_array($purview, $purviews)) {
                continue;
            }
            if (!empty($v['types'])) {
                $weight[$v['types']][] = $v['weight'];
                $arr[$v['types']]['types'] = ['key' => $v['types'], 'jumpurl' => $v['jumpurl'], 'name' => $v['_types']];
                $arr[$v['types']]['subMenu'][] = $v;
            }
        }
        foreach ($arr as $k => $v) {
            array_multisort($weight[$k], SORT_DESC, $arr[$k]['subMenu']);
        }
        return $arr;
    }
}
