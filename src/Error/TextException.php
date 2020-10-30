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
        $output = new Output();
        $this->result = $output->withCode($code,$param);
    }

    public function getResult()
    {
        return $this->result;
    }
}
