<?php
/**
 * 模板解析加载
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use SlimCMS\Error\TextException;
use SlimCMS\Interfaces\TemplateInterface;

class Template implements TemplateInterface
{
    private static $cacheFile = '';
    private static $replacecode = ['search' => [], 'replace' => []];

    private static function parseTemplate($tplfile, $cachefile)
    {
        if ($fp = @fopen(CSROOT . $tplfile, 'r')) {
            $template = @fread($fp, filesize(CSROOT . $tplfile));
            fclose($fp);
        } elseif ($fp = @fopen($filename = substr(CSROOT . $tplfile, 0, -4) . '.php', 'r')) {
            $template = self::getPHPTemplate(@fread($fp, filesize($filename)));
            fclose($fp);
        } else {
            throw new TextException(21052, ['title' => $tplfile]);
        }
        if (!@$fp = fopen(CSDATA . $cachefile, 'w')) {
            throw new TextException(21053, ['title' => $cachefile]);
        }
        $template = self::formatTemplate($template);

        flock($fp, 2);
        fwrite($fp, $template);
        fclose($fp);
    }

    private static function formatTemplate($template)
    {
        $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

        $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = preg_replace_callback("/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/is", [__CLASS__, 'evalTag'], $template);
        $template = preg_replace_callback("/[\n\r\t]*\{list\s+(.+?)\s*\}[\n\r\t]*/is", [__CLASS__, 'listTag'], $template);
        $template = preg_replace("/\{\/list\}/i", "<?php }} ?>", $template);
        $template = preg_replace_callback("/[\n\r\t]*\{data\s+(.+?)\s*\}[\n\r\t]*/is", [__CLASS__, 'dataTag'], $template);
        $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
        $template = preg_replace_callback("/$var_regexp/s", [__CLASS__, 'addquote'], $template);
        $template = preg_replace_callback("/\<\?\=\<\?\=$var_regexp\?\>\?\>/s", [__CLASS__, 'addquote'], $template);

        $template = preg_replace_callback("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/is", [__CLASS__, 'loadTemplateTag'], $template);
        $template = preg_replace_callback("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is", [__CLASS__, 'echoTag'], $template);

        $template = preg_replace_callback("/[\n\r\t]*\{url\s+(.+?)\}[\n\r\t]*/is", [__CLASS__, 'urlTag'], $template);

        $template = preg_replace_callback("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/is", [__CLASS__, 'ifTag'], $template);
        $template = preg_replace_callback("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is", [__CLASS__, 'elseifTag'], $template);
        $template = preg_replace("/\{else\}/i", "<? } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/i", "<? } ?>", $template);

        $template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", [__CLASS__, 'loopTag'], $template);
        $template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", [__CLASS__, 'loopTag'], $template);
        $template = preg_replace("/\{\/loop\}/i", "<? } ?>", $template);

        $template = preg_replace_callback("/[\n\r\t]*\{for\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/is", [__CLASS__, 'forTag'], $template);
        $template = preg_replace("/\{\/for\}/i", "<? } ?>", $template);

        $template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
        if (!empty(self::$replacecode)) {
            $template = str_replace(self::$replacecode['search'], self::$replacecode['replace'], $template);
        }
        $template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

        $template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", [__CLASS__, 'transamp'], $template);
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1??'';?>", $template);
        return $template;
    }

    private static function evalTag($matches)
    {
        $php = $matches[1];
        $php = str_replace('\"', '"', $php);
        $i = count(self::$replacecode['search']);
        self::$replacecode['search'][$i] = $search = "<!--EVAL_TAG_$i-->";
        self::$replacecode['replace'][$i] = "<?php $php?>";
        return $search;
    }

    private static function getPHPTemplate($content)
    {
        $pos = strpos($content, "\n");
        return $pos !== false ? substr($content, $pos + 1) : $content;
    }

    private static function transamp($matches)
    {
        $str = $matches[0];
        $str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }

    private static function addquote($matches)
    {
        $var = '<?=' . $matches[1] . '?>';
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
    }

    private static function loadTemplateTag($matches)
    {
        $param = str_replace(['<?=', '?>'], ["'.", ".'"], $matches[1]);
        $expr = '<?php include SlimCMS\Core\Template::loadTemplate(\'' . $param . '\'); ?>';
        return self::stripvtags($expr);
    }

    private static function dataTag($matches)
    {
        $tagcode = $matches[1];
        $row = [];
        $tags = explode(' ', $tagcode);
        foreach ($tags as $v) {
            if ($v) {
                $v = preg_replace('/["\']/', '', $v);
                list($key, $val) = explode('=', $v);
                if (strpos($val, '$') !== false) {
                    $val = str_replace("\\\"", "\"", preg_replace("/\[([\w\-\.]+)\]/s", "['\\1']", trim($val)));
                    $row[trim($key)] = '\'.(isset(' . $val . ')?' . $val . ':\'\').\'';
                } else {
                    $row[trim($key)] = trim($val);
                }
            }
        }
        $data = json_encode($row);
        $func = aval($row, 'func');
        $key = aval($row, 'key', 'count');
        $i = count(self::$replacecode['search']);
        self::$replacecode['search'][$i] = $search = "<!--" . __FUNCTION__ . "_$i-->";
        self::$replacecode['replace'][$i] = "<?php \$_tagdata = \app\model\main\TagsModel::$func('$data'); echo aval(\$_tagdata,'$key');?>";
        return $search;
    }

    private static function echoTag($matches)
    {
        $expr = '<?php echo ' . $matches[1] . '??\'\'; ?>';
        return self::stripvtags($expr);
    }

    private static function urlTag($matches)
    {
        $param = str_replace(['<?=', '?>'], ['".', '."'], $matches[1]);
        if (strpos($param, ' ')) {
            list($url, $name) = explode(' ', $param);
            $expr = '<?php echo rewriteUrl("' . $url . '","' . $name . '"); ?>';
        } else {
            $expr = '<?php echo rewriteUrl("' . $param . '"); ?>';
        }
        return self::stripvtags($expr);
    }

    private static function ifTag($matches)
    {
        $expr = $matches[1] . '<?php if(' . $matches[2] . ') { ?>' . $matches[3];
        return self::stripvtags($expr);
    }

    private static function elseifTag($matches)
    {
        $expr = $matches[1] . '<?php } elseif(' . $matches[2] . ') { ?>' . $matches[3];
        return self::stripvtags($expr);
    }

    private static function loopTag($matches)
    {
        if (!empty($matches[3])) {
            $expr = '<?php if(!empty(' . $matches[1] . ') && is_array(' . $matches[1] . ')) foreach(' . $matches[1] . ' as ' . $matches[2] . ' => ' . $matches[3] . ') { ?>';
        } else {
            $expr = '<?php if(!empty(' . $matches[1] . ') && is_array(' . $matches[1] . ')) foreach(' . $matches[1] . ' as ' . $matches[2] . ') { ?>';
        }
        return self::stripvtags($expr);
    }

    private static function forTag($matches)
    {
        $expr = '<?php for($' . $matches[1] . '=' . $matches[2] . ';$' . $matches[1] . '<' . $matches[3] . ';$' . $matches[1] . '++) { ?>';
        return self::stripvtags($expr);
    }

    /**
     * $$v[xxx]类似的标签解析成${$v[xxx]}(php7下解释成$$v下的[xxx]) zhucy 2017.2.20
     * @param unknown_type $code
     */
    private static function parsePHP($code)
    {
        if (is_array($code)) {
            return array_map([__CLASS__, 'parsePHP'], $code);
        }
        if (preg_match('/\$\$([_\w\d\[\]\'\']+)/', $code)) {
            $code = preg_replace('/\$\$([_\w\d\[\]\'\']+)/', '${$\\1}', $code);
        }
        return $code;
    }

    private static function stripvtags($expr, $statement = '')
    {
        $expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        $expr = self::parsePHP($expr);
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr . $statement;
    }

    private static function listTag($matches)
    {
        $tagcode = $matches[1];
        $row = [];
        $tags = explode(' ', $tagcode);
        foreach ($tags as $v) {
            if ($v) {
                $v = preg_replace('/["\']/', '', $v);
                list($key, $val) = explode('=', $v);
                if (strpos($val, '$') !== false) {
                    $val = str_replace("\\\"", "\"", preg_replace("/\[([\w\-\.]+)\]/s", "['\\1']", trim($val)));
                    $row[trim($key)] = '\'.(isset(' . $val . ')?' . $val . ':\'\').\'';
                } else {
                    $row[trim($key)] = trim($val);
                }
            }
        }
        $indexk = aval($row, 'index-key', 'k');
        $indexv = aval($row, 'index-value', 'v');

        $data = json_encode($row);
        $func = aval($row, 'func', 'dataList');
        $listkey = aval($row, 'listKey', 'infolist');
        $i = count(self::$replacecode['search']);
        self::$replacecode['search'][$i] = $search = "<!--" . __FUNCTION__ . "_$i-->";
        self::$replacecode['replace'][$i] = "<?php \$_list = \app\model\main\TagsModel::$func('$data'); if(!empty(\$_list['$listkey'])){foreach(\$_list['$listkey'] as \${$indexk}=>\${$indexv}){?>";
        return $search;
    }

    /**
     * {@inheritDoc}
     */
    public static function loadTemplate($file, $force = false)
    {
        if (strpos($file, CSROOT) === 0) {
            $force = true;
            $file = str_replace(CSROOT, '', $file);
            $file = preg_replace('/^\/template\//', '', $file);
        }
        if ($force === false) {
            $tpldir = '/template/' . CURSCRIPT . '/';

            $tplfile = $tpldir . $file . '.htm';
            if (!is_file(CSROOT . $tplfile)) {
                $tplfile = dirname($tplfile) . '/default/' . substr($tplfile, (strlen(dirname($tplfile)) + 1));
                if (!is_file(CSROOT . $tplfile)) {
                    $tplfile = $tpldir . 'default/' . substr($tplfile, (strlen(dirname($tplfile)) + 1));
                }
            }
            if (!is_file(CSROOT . $tplfile)) {
                $tplfile = '/template/default/' . basename($file) . '.htm';
            }
            self::$cacheFile = 'template/' . CURSCRIPT . '_' . str_replace('/', '_', $file) . '.tpl.php';
        } else {
            $tplfile = '/template/' . $file . '.htm';
            if (!is_file(CSROOT . $tplfile)) {
                $tplfile = '/template/default/' . basename($file) . '.htm';
            }
            self::$cacheFile = 'template/' . md5($file) . '.tpl.php';
        }

        if (!is_file(CSROOT . $tplfile)) {
            throw new TextException(21052, ['title' => $tplfile]);
        }
        if (self::$cacheFile) {
            self::checktplrefresh($tplfile, self::$cacheFile);
            return CSDATA . self::$cacheFile;
        }
    }

    private static function checkTplRefresh($maintpl, $cachefile)
    {
        $ftime = is_file(CSDATA . $cachefile) ? filemtime(CSDATA . $cachefile) : '';
        if (empty($ftime) || @filemtime(CSROOT . $maintpl) > $ftime) {
            self::parseTemplate($maintpl, $cachefile);
            return TRUE;
        }
        return FALSE;
    }

}