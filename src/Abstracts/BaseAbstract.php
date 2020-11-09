<?php
/**
 * 默认控制类
 */

namespace SlimCMS\Abstracts;

use App\Core\Redis;
use slimCMS\Core\Request;
use slimCMS\Core\Response;
use SlimCMS\Core\Table;
use SlimCMS\Interfaces\OutputInterface;

abstract class BaseAbstract
{
    /**
     * 请求对象实例
     * @var Request
     */
    protected $request;

    /**
     * 响应对象实例
     * @var Response
     */
    protected $response;

    protected $output;

    protected $container;

    /**
     * redis实例
     * @var \Redis|null
     *
     */
    protected $redis;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->output = $this->request->getOutput();
        $this->container = $this->request->getContainer();
        $this->redis = $this->container->get(Redis::class);
    }

    public function t($name): Table
    {
        static $objs = [];
        $className = ucfirst($name);
        $classname = '\App\Table\\' . $className . 'Table';
        if (!class_exists($classname)) {
            $classname = 'App\Core\Table';
        }
        if (empty($objs[$name])) {
            $objs[$name] = new $classname($this->container, $name);
        }
        return $objs[$name];
    }

    /**
     * 获取外部传入数据
     * @param $name
     * @param string $type
     * @return array|mixed|\都不存在时的默认值|null
     */
    public function input(string $name, string $type = 'string')
    {
        return $this->request->input($name, $type);
    }

    /**
     * 数据格式输出
     * @param $result
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function response(OutputInterface $output = null)
    {
        $output = $output ?? $this->output;
        return $this->response->output($output);
    }
}