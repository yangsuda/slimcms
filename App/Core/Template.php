<?php
/**
 * 模板解析加载
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Core;

class Template extends \SlimCMS\Core\Template
{
    protected static function urlTag($matches)
    {
        $param = str_replace(['<?=', '?>'], ['".', '."'], $matches[1]);
        if (strpos($param, ' ')) {
            list($url, $name) = explode(' ', $param);echo 's';exit;
            $expr = '<?php echo \App\Core\Forms::url("' . $url . '","' . $name . '"); ?>';
        } else {
            $expr = '<?php echo \App\Core\Forms::url("' . $param . '"); ?>';
        }
        return self::stripvtags($expr);
    }

    protected static function loadTemplateTag($matches)
    {
        $param = str_replace(['<?=', '?>'], ["'.", ".'"], $matches[1]);
        $expr = '<?php include App\Core\Template::loadTemplate(\'' . $param . '\'); ?>';
        return self::stripvtags($expr);
    }
}
