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

    private $template = 'prompt';

    /**
     * 容器
     * @var \DI\Container|mixed
     */
    private $container;

    private $attribute = [];

    /**
     * {@inheritdoc}
     */
    public function __invoke(App $app)
    {
        $this->app = $app;
        $this->container = $app->getContainer()->get('DI\Container');
        return $this;
    }

    public function __set($name, $value)
    {
        $this->attribute[$name] = $value;
    }

    public function __get($name)
    {
        return aval($this->attribute, $name);
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
    public function withCode(int $code, $param = []): OutputInterface
    {
        $clone = clone $this;
        $clone->code = $code;
        $clone->msg = $clone->promptMsg($code, $param);
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
        $clone->data = array_merge($clone->data, $data);
        return $clone;
    }

    /**
     * {@inheritDoc}
     */
    public function withTemplate(string $template): OutputInterface
    {
        $clone = clone $this;
        $clone->template = $template;
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
    public function withReferer(string $url): OutputInterface
    {
        $clone = clone $this;
        $clone->referer = $url;
        return $clone;
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
