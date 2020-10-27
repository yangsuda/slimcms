<?php
/**
 * 输出类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use JsonSerializable;

class Output implements JsonSerializable
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var array|object|null
     */
    private $data;

    /**
     * @var Error|null
     */
    private $error;

    /**
     * @param int                   $code
     * @param array|object|null     $data
     * @param Error|null      $error
     */
    public function __construct(
        int $code = 200,
        $data = null,
        ?Error $error = null
    ) {
        $this->code = $code;
        $this->data = $data;
        $this->error = $error;
    }

    /**
     * @return int
     */
    public function getcode(): int
    {
        return $this->code;
    }

    /**
     * @return array|null|object
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Error|null
     */
    public function getError(): ?Error
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $error = [
            'code' => $this->code,
        ];

        if ($this->data !== null) {
            $error['data'] = $this->data;
        } elseif ($this->error !== null) {
            $error['error'] = $this->error;
        }

        return $error;
    }
}
