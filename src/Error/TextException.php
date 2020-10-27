<?php

declare(strict_types=1);

namespace SlimCMS\Error;

use Exception;
use SlimCMS\Core\Output;

class TextException extends Exception
{
    private $result;
    public function __construct($result)
    {
        $this->result = Output::result($result);
    }

    public function getResult()
    {
        return $this->result;
    }
}
