<?php
/**
 * 输出类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use SlimCMS\Interfaces\OutputInterface;

class Output implements OutputInterface
{
    /**
     * @var int
     */
    static private $code;

    /**
     * @var array|object|null
     */
    static private $data;

    /**
     * @var array|object|null
     */
    static private $msg = '';

    static private $referer;

    static private $showType = 3;

    /**
     * {@inheritDoc}
     */
    static public function result($code, $data = [], $para = [], $referer = '')
    {
        if (is_array($code)) {
            self::$code = aval($code, 'code');
            self::$msg = !empty($code['param']) ? self::promptMsg($code['code'], $code['param']) : aval($code, 'msg');
            self::$data = aval($code, 'data', []);
            self::$referer = aval($code, 'referer');
            self::$showType = aval($code, 'showType', 3);
        } else {
            self::$code = $code;
            self::$msg = self::promptMsg($code, $para);
            self::$data = $data;
            self::$referer = $referer;
        }
        return new self;
    }

    /**
     * 返回提示代码对应信息
     * @param $code
     * @param array $para
     * @return mixed|string
     */
    static private function promptMsg($code, $para = []): string
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
                $str = self::promptMsg($para);
            }
        }
        return $str;
    }

    static public function getShowType(): int
    {
        return (int)self::$showType;
    }

    static public function getMsg(): string
    {
        return (string)self::$msg;
    }

    static public function getCode(): int
    {
        return (int)self::$code;
    }

    static public function getReferer(): string
    {
        return (string)self::$referer;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'code' => self::$code,
            'msg' => self::$msg,
            'data' => self::$data,
            'referer' => self::$referer,
        ];
    }
}
