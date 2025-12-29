<?php

declare(strict_types=1);

namespace App\Model\req;

use SlimCMS\Abstracts\ReqAbstract;

class Forms_fieldsReq extends ReqAbstract
{
    protected function identifier(array $param, $words = null): void
    {
        isset($words) && $this->where['identifier'] = $words;
    }

    protected function formid(array $param, $words = null): void
    {
        isset($words) && $this->where['formid'] = $words;
    }

    protected function datatype(array $param, $words = null): void
    {
        isset($words) && $this->where['datatype'] = $words;
    }

    protected function available(array $param, $words = null): void
    {
        isset($words) && $this->where['available'] = $words;
    }

    protected function infront(array $param, $words = null): void
    {
        isset($words) && $this->where['infront'] = $words;
    }

    protected function required(array $param, $words = null): void
    {
        isset($words) && $this->where['required'] = $words;
    }

    protected function unique(array $param, $words = null): void
    {
        isset($words) && $this->where['unique'] = $words;
    }

    protected function search(array $param, $words = null): void
    {
        isset($words) && $this->where['search'] = $words;
    }

    protected function orderby(array $param, $words = null): void
    {
        isset($words) && $this->where['orderby'] = $words;
    }

    protected function inlist(array $param, $words = null): void
    {
        isset($words) && $this->where['inlist'] = $words;
    }

    protected function inlistcp(array $param, $words = null): void
    {
        isset($words) && $this->where['inlistcp'] = $words;
    }

    protected function forbidedit(array $param, $words = null): void
    {
        isset($words) && $this->where['forbidedit'] = $words;
    }

    protected function isexport(array $param, $words = null): void
    {
        isset($words) && $this->where['isexport'] = $words;
    }
}
