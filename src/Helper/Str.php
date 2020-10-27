<?php
/**
 * 字符串处理类
 */

namespace SlimCMS\Helper;

class Str
{

    public static function random($length, $numeric = 0)
    {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        if ($numeric) {
            $hash = '';
        } else {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed[mt_rand(0, $max)];
        }
        return $hash;
    }

    public static function delHtml($document, $type = 1)
    {
        if ($type == 1) {
            $document = preg_replace("#<\/p>#i", "\n", $document);
            $document = preg_replace("#<br>#i", "\n", $document);
            $document = preg_replace("#<br \/>#i", "\n", $document);
            $search = [
                "'<script[^>]*?>.*?</script>'si", // 去掉 javascript
                "'<[\/\!]*?[^<>]*?>'si", // 去掉 HTML 标记
                "'([\r\n])[\s]+'", // 去掉空白字符
                "'#p#|#e#'i", // 去掉分页标记
                "'&(quot|#34);'i", // 替换 HTML 实体
                "'&(amp|#38);'i",
                "'&(lt|#60);'i",
                "'&(gt|#62);'i",
                "'&(nbsp|#160);'i"];

            $replace = [
                "",
                "",
                "",
                "\\1",
                "\"",
                "&",
                "<",
                ">",
                " "];

            $document = preg_replace($search, $replace, $document);
            return trim(strip_tags($document));
        }
        //过滤非汉字字母数字字符
        if ($type == 2) {
            return preg_replace("/&[A-Za-z].*;|[^" . chr(0x80) . "-" . chr(0xff) . "\w,]/isU", "", strip_tags($document));
        }
        //过滤掉CSS、JS、HTML代码
        if ($type == 3) {
            $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", "", $document);
            $alltext = "";
            $start = 1;
            for ($i = 0; $i < strlen($str); $i++) {
                if ($start == 0 && $str[$i] == ">") {
                    $start = 1;
                } else if ($start == 1) {
                    if ($str[$i] == "<") {
                        $start = 0;
                        $alltext .= " ";
                    } else if (ord($str[$i]) > 31) {
                        $alltext .= $str[$i];
                    }
                }
            }
            $alltext = preg_replace("/&([^;&]*)(;|&)/", "", $alltext);
            $alltext = preg_replace("/[ ]+/s", " ", $alltext);
            return $alltext;
        }
    }

    /**
     * 截取字符串
     * @param $str
     * @param $start
     * @param $length
     * @param string $etc
     * @return string
     */
    public static function substr($str, $start, $length, $etc = '')
    {
        $i = 0;
        //完整排除之前的UTF8字符
        while ($i < $start) {
            $ord = ord($str[$i]);
            if ($ord < 192) {
                $i++;
            } elseif ($ord < 224) {
                $i += 2;
            } else {
                $i += 3;
            }
        }
        //开始截取
        $result = '';
        while ($i < $start + $length && $i < strlen($str)) {
            $ord = ord($str[$i]);
            if ($ord < 192) {
                $result .= $str[$i];
                $i++;
            } elseif ($ord < 224) {
                $result .= $str[$i] . $str[$i + 1];
                $i += 2;
            } else {
                $result .= $str[$i] . $str[$i + 1] . $str[$i + 2];
                $i += 3;
            }
        }
        if ($i < strlen($str)) {
            $result .= $etc;
        }
        return $result;
    }

    public static function substrDot($str, $len, $dot = '...')
    {
        $new_str = self::substr($str, $len);
        if (strlen($str) > strlen($new_str)) {
            return $new_str . $dot;
        }
        return $new_str;
    }

    public static function round($val, $lang = 0)
    {
        return !empty($val) ? round($val, $lang) : '';
    }

    /**
     * 过滤内容数据
     * @param type $html 内容
     */
    public static function filterHtml($html)
    {
        $html = preg_replace(array("'<div.*?>'si", "'</div>'si", "'<p><img(.*?)>'si"), array("", "", "<p style='text-align:center'><img\\1></p>\n<p>"), $html);
        $html = self::filterDangerImg($html);
        return preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", "", $html);
    }

    /**
     * 过滤掉字符串中带有非图片url的图片链接
     * @param $body 要过滤的字符串
     * @return string 过滤后的字符串
     */
    private static function filterDangerImg($html)
    {
        preg_match_all("/<img src(.*?)=(.*?)>/i", $html, $match);
        $danger_urls = [];
        $replace = [];
        if (count($match) == 3) {
            foreach ($match[2] as $v) {
                if (preg_match("/(.php|\?|&)/i", $v, $m)) {
                    $danger_urls[] = $v;
                    $replace[] = '';
                    continue;
                }
                if (!preg_match("/(\.gif|\.jpg|\.png)/i", $v, $m)) {
                    $danger_urls[] = $v;
                    $replace[] = '';
                    continue;
                }
            }
        }
        return str_replace($danger_urls, $replace, $html);
    }

    /**
     * 数据序列化
     * @param type $str 需要处理的数据
     * @return string
     */
    public static function serializeData($str)
    {
        if ($str) {
            //不知为何有时会莫名出来choices=,先过滤掉
            $arr = explode("\n", str_replace('choices=', '', $str));
            $row = array();
            $i = 0;
            if (!preg_match('/=/', $str)) {
                return $str;
            }
            foreach ($arr as $v) {
                $i++;
                list($keys, $value) = explode('=', $v);
                $keys = trim($keys);
                if (!empty($row[$keys])) {
                    $row[$i . '#' . $keys] = trim($value);
                } else {
                    $row[$keys] = trim($value);
                }
            }
            return serialize($row);
        }
        return '';
    }

    /**
     * 数据反序列化
     * @param type $str 需要处理的数据
     * @return string
     */
    public static function unserializeData($data)
    {
        $str = '';
        if (!empty($data)) {
            $arr = unserialize($data);
            if (empty($arr)) {
                return $data;
            }
            foreach ($arr as $k => $v) {
                $str .= $k . '=' . $v . "\n";
            }
        }
        return trim($str, "\n");
    }
}
