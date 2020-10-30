<?php

declare(strict_types=1);

namespace SlimCMS\Error;

use Exception;
use SlimCMS\Core\Output;

class TextException extends Exception
{
    private $result;

    public function __construct($code, $param = [])
    {
        //应用实例引入还要传多一个参数，此处暂时不走容器，直接new了
        $output = new Output();
        $this->result = $output->withCode($code,$param);
    }

    public function getResult()
    {
        return $this->result;
    }
}
