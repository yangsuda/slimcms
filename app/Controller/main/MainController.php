<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\main;

use app\Repository\SysenumRepository;
use Psr\Http\Message\ResponseInterface;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\ImageCode;
use SlimCMS\Helper\Str;
use SlimCMS\Interfaces\UploadInterface;

class MainController extends ControlAbstract
{
    /**
     * 后台仪表盘
     * 当前管理员信息通过 AdminAuthMiddleware 注入的 request attribute 'admin' 获取
     */
    public function index(): ResponseInterface
    {
        $file = $this->request->getUploadedFiles();

        var_dump($file); // bool(true)
        exit;
        return $this->view($this->output, 'index');
    }

    /**
     * 联动菜单数据
     * @return \Psr\Http\Message\MessageInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function enumsData()
    {
        $egroup = $this->input('egroup');
        $list = $this->r(SysenumRepository::class)
            ->withWhere(['egroup' => $egroup, 'evalueOverNil' => 1])
            ->fetchList('id,ename,evalue,reid');
        return $this->json($this->output->withData(['list' => $list]));
    }

    /**
     * 生成验证码
     */
    public function captcha()
    {
        ImageCode::doimg($this->session());
    }

    /**
     * 获取 FORMHASH（用于 AJAX 请求）
     */
    public function formHash()
    {
        $data = ['formHash' => Str::formHash($this->session())];
        $output = $this->output->withData($data);
        return $this->json($output);
    }

    /**
     * 兜底处理：所有未匹配的路由统一返回友好错误页面
     */
    public function notFound()
    {
        if (strpos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->json($this->output->withCode(21009));
        }
        return $this->view($this->output, 'error');
    }
}
