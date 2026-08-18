<?php

/**
 * 后台控制类
 */
declare(strict_types=1);

namespace app\Controller\admin;

use app\Core\Forms;
use app\Model\entity\AdminEntity;
use app\Service\admin\AuthService;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Error\TextException;
use SlimCMS\Interfaces\OutputInterface;

class AdminController extends ControlAbstract
{
    protected AdminEntity|null $admin = null;

    public function __construct(App $app, ServerRequestInterface $request = null)
    {
        parent::__construct($app, $request);
        $this->admin = $this->request->getAttribute('admin');
        define('MANAGE', '1');
    }

    /**
     * 权限检测
     * @param string|null $auth
     * @return void
     * @throws TextException
     */
    protected function checkAllow(string $auth = null)
    {
        $auth = $auth ?? $this->p;
        $res = $this->authService()->checkAllow($this->admin, $auth);
        if ($res->getCode() != 200) {
            if ($this->determineContentType() == 'application/json') {
                throw new TextException($res->getCode(), $res->getMsg());
            } else {
                header('location:' . $res->getReferer() ?: '/admin/index');
                exit;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function view(OutputInterface $output = null, string $template = ''): MessageInterface
    {
        $output = $output ?? $this->output;
        $data = [];
        $data['leftMenu'] = $this->leftMenu();
        if (empty($output->getData()['admin'])) {
            $data['admin'] = $this->admin->toArray();
        }
        $output = $output->withData($data);
        return parent::view($output, $template);
    }

    private function leftMenu()
    {
        $param = ['fid' => 1, 'ischeck' => 1, 'pagesize' => 200, 'cacheTime' => 60, 'order' => 'weight', 'noinput' => 1];
        $res = $this->forms()->dataList($param)->getData();
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
            if (!$this->admin->groupidEntity()?->isSuperAdmin() && !$this->admin->groupidEntity()?->hasPurview($purview)) {
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

    protected function authService(): AuthService
    {
        return $this->i(AuthService::class);
    }

    protected function forms(): Forms
    {
        return $this->i(Forms::class);
    }
}
