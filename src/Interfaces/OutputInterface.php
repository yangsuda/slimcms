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

    public function getMsg(): string;

    public function getCode(): int;

    public function getReferer(): string;

    public function analysisTemplate();
}
