<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;


use JsonSerializable;
use Slim\App;

interface OutputInterface extends JsonSerializable
{
    /**
     * 当尝试以调用函数的方式调用一个对象时，此方法会被自动调用
     * @param App $app
     * @return mixed
     */
    public function __invoke(App $app);

    /**
     * 返回提示数据
     * @param $res
     * @return array
     */
    public function result($res = []): self;

    /**
     * 返回提示文字
     * @return string
     */
    public function getMsg(): string;

    /**
     * 返回提示代码
     * @return int
     */
    public function getCode(): int;

    /**
     * 设置返回提示代码
     * @param int $code
     * @return OutputInterface
     */
    public function withCode(int $code): self;

    /**
     * 返回输出数据
     * @return int
     */
    public function getData(): array;

    /**
     * 设置返回输出数据
     * @param int $data
     * @return OutputInterface
     */
    public function withData(array $data): self;

    /**
     * 返回跳转URL
     * @return string
     */
    public function getReferer(): string;

    /**
     * 解析模板
     * @return mixed
     */
    public function analysisTemplate(): string;

    /**
     * 对象转成json时处理方法
     * @return array
     */
    public function jsonSerialize(): array;
}
