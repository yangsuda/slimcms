<?php

declare(strict_types=1);

namespace app\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class Forms_fieldsReq extends ReqAbstract
{
    protected function identifier(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function formid(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function datatype(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function available(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function infront(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function required(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function unique(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function search(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function orderby(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function inlistcp(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function forbidedit(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }

    protected function isexport(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }
    protected function ischeck(array $param, $words = null): void
    {
        isset($words) && $this->where[__FUNCTION__] = $words;
    }
}
