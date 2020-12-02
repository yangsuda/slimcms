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
        $config = getConfig();
        $param = str_replace(['<?=', '?>'], ['".', '."'], $matches[1]);
        if (!empty($config['cfg']['urlEncrypt']) && strpos($param, '\'+')) {
            $expr = '<?php echo "' . $param . '"; ?>';
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
