<?php
/**
 * 输出数据整理类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use Slim\App;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Interfaces\TemplateInterface;

class Output implements OutputInterface
{
    private $app;
    /**
     * @var int
     */
    private $code = 200;

    /**
     * @var array|object|null
     */
    private $data = [];

    /**
     * @var array|object|null
     */
    private $msg = '';

    private $referer;

    private $jsonCallback;

    private $template = 'prompt';

    /**
     * 容器
     * @var \DI\Container|mixed
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function __invoke(App $app)
    {
        $this->app = $app;
        $this->container = $app->getContainer()->get('DI\Container');
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function result($res = []): OutputInterface
    {
        if (is_numeric($res)) {
            $this->code = $res;
            $this->msg = $this->promptMsg($res);
        } else {
            !empty($res['code']) && $this->code = $res['code'];
            $this->msg = $this->promptMsg($this->code, aval($res, 'param'));
            !empty($res['data']) && $this->data = $res['data'];
            !empty($res['referer']) && $this->referer = $res['referer'];
            !empty($res['jsonCallback']) && $this->jsonCallback = $res['jsonCallback'];
            !empty($res['template']) && $this->template = $res['template'];
        }
        return $this;
    }

    /**
     * 返回提示代码对应信息
     * @param $code
     * @param array $para
     * @return mixed|string
     */
    private function promptMsg($code, $para = []): string
    {
        $prompt = require CSROOT . 'config/prompt.php';
        $prompt += require dirname(dirname(__FILE__)) . '/Config/prompt.php';
        $str = $prompt[$code];
        if ($para) {
            if (is_array($para)) {
                extract($para);
                eval("\$str = \"$str\";");
            } elseif (is_string($para)) {
                $str = $para;
            } elseif (is_numeric($para)) {
                $str = $this->promptMsg($para);
            }
        }
        return $str;
    }

    public function getJsonCallback(): string
    {
        return (string)$this->jsonCallback;
    }

    /**
     * {@inheritDoc}
     */
    public function getMsg(): string
    {
        return (string)$this->msg;
    }

    /**
     * {@inheritDoc}
     */
    public function getCode(): int
    {
        return (int)$this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function withCode(int $code): OutputInterface
    {
        $clone = clone $this;
        $this->code = $code;
        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        return (array)$this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function withData(array $data): OutputInterface
    {
        $clone = clone $this;
        $this->data = array_merge($this->data, $data);
        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function getReferer(): string
    {
        return (string)$this->referer;
    }

    /**
     * {@inheritDoc}
     */
    public function analysisTemplate(): string
    {
        if ($this->template) {
            $callback = function_exists('ob_gzhandler') ? 'ob_gzhandler' : '';
            ob_start($callback);
            $code = $this->code;
            $msg = $this->msg;
            $referer = $this->referer;
            $data = $this->data;
            $cfg = $this->container->get('cfg');
            include_once($this->container->get(TemplateInterface::class)::loadTemplate($this->template));
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'code' => $this->code,
            'msg' => $this->msg,
            'data' => $this->data,
        ];
        !empty($this->referer) && $data['referer'] = $this->referer;
        return $data;
    }
}
