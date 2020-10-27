<?php

declare(strict_types=1);

namespace SlimCMS\Error;

use Exception;

class TextException extends Exception
{
    public function __construct($result)
    {
        if (is_numeric($result)) {
            $result = ['code' => $result, 'msg' => $this->promptMsg($result), 'data' => [], 'referer' => ''];
        }
        parent::__construct(aval($result,'msg'), aval($result,'code'));
    }

    /**
     * 返回提示代码对应信息
     * @param $code
     * @param array $para
     * @return mixed|string
     */
    private function promptMsg($code, $para = array()): string
    {
        $prompt = require CSROOT . 'config/prompt.php';
        $prompt += require dirname(dirname(__FILE__)) . '/Config/prompt.php';
        $str = $prompt[$code];
        if ($para) {
            if (is_array($para)) {
                extract($para);
                eval("\$str = \"$str\";");
            } elseif (is_string($para)) {
                $str = $para;
            } elseif (is_numeric($para)) {
                $str = $this->promptMsg($para);
            }
        }
        return $str;
    }
}
