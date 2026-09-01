<?php
/**
 * 后台主控制器
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Controller\main;

use app\Repository\SysenumRepository;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use SlimCMS\Abstracts\ControlAbstract;
use SlimCMS\Helper\Captcha;

class MainController extends ControlAbstract
{
    private SysenumRepository $sysenumRepository;

    public function __construct(App $app, SysenumRepository $sysenumRepository)
    {
        parent::__construct($app);
        $this->sysenumRepository = $sysenumRepository;
    }

    public function index(): ResponseInterface
    {
        return $this->view($this->output, 'index');
    }

    /**
     * 联动菜单数据
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function enumsData(): ResponseInterface
    {
        $egroup = $this->input('egroup');
        $list = $this->sysenumRepository
            ->withWhere(['egroup' => $egroup, 'evalueOverNil' => 1])
            ->fetchList('id,ename,evalue,reid');
        return $this->json($this->output->withData(['list' => $list]));
    }

    /**
     * 生成验证码（PSR-7 兼容：不使用 header/exit，走完整中间件管线）
     */
    public function captcha(): ResponseInterface
    {
        $png = Captcha::generate($this->session());
        $response = $this->response
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('Content-Length', (string)\strlen($png))
            // 验证码不应被浏览器缓存，避免刷新不换图
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache');
        $response->getBody()->write($png);
        return $response;
    }

    /**
     * 兜底处理：所有未匹配的路由统一返回友好错误页面
     */
    public function notFound():ResponseInterface
    {
        if (strpos($this->request->getHeaderLine('Accept'), 'application/json') !== false) {
            return $this->json($this->output->withCode(21009));
        }
        return $this->view($this->output, 'error');
    }
}