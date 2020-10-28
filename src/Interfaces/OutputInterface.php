<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;


use JsonSerializable;

interface OutputInterface extends JsonSerializable
{
    /**
     * 返回提示数据
     * @param $code
     * @return array
     */
    public static function result($code): self;

    public static function getMsg(): string;

    public static function getCode(): int;

    public static function getReferer(): string;

    public static function analysisTemplate();
}
