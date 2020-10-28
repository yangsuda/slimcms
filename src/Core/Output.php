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
    private static $code = 200;

    /**
     * @var array|object|null
     */
    private static $data = [];

    /**
     * @var array|object|null
     */
    private static $msg = '';

    private static $referer = '';

    private static $showType = 3;

    private static $template = '';

    /**
     * {@inheritDoc}
     */
    public static function result($code): OutputInterface
    {
        if (is_numeric($code)) {
            self::$code = $code;
            self::$msg = self::promptMsg($code);
        } else {
            !empty($code['code']) && self::$code = $code['code'];
            self::$msg = self::promptMsg(self::$code, aval($code, 'param'));
            !empty($code['data']) && self::$data = $code['data'];
            !empty($code['referer']) && self::$referer = $code['referer'];
            !empty($code['showType']) && self::$showType = $code['showType'];
            !empty($code['template']) && self::$template = $code['template'];
        }
        return new self();
    }

    /**
     * 返回提示代码对应信息
     * @param $code
     * @param array $para
     * @return mixed|string
     */
    private static function promptMsg($code, $para = []): string
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

    public static function getShowType(): int
    {
        return (int)self::$showType;
    }

    public static function getMsg(): string
    {
        return (string)self::$msg;
    }

    public static function getCode(): int
    {
        return (int)self::$code;
    }

    public static function getReferer(): string
    {
        return (string)self::$referer;
    }

    /**
     * 解析模板
     * @return false|string
     * @throws \SlimCMS\Error\TextException
     */
    public static function analysisTemplate()
    {
        if (self::$template) {
            $callback = function_exists('ob_gzhandler') ? 'ob_gzhandler' : '';
            ob_start($callback);
            include_once(Template::loadTemplate(self::$template));
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
        return '';
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
