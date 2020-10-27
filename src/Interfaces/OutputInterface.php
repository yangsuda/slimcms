<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;


use JsonSerializable;

interface OutputInterface extends JsonSerializable
{
    /**
     * 返回提示数据
     * @param $code
     * @param array $data
     * @param array $para
     * @param string $referer
     * @return array
     */
    static public function result($code, $data = [], $para = [], $referer = '');

    static public function getMsg():string;

    static public function getCode():int;

    static public function getReferer():string;
}
