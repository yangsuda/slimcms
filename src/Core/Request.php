<?php
/**
 * 外部请求处理类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use SlimCMS\Error\TextException;

class Request extends Message
{
    /**
     * 获取外部传参
     * @param $name
     * @param string $type
     * @return array|mixed|\都不存在时的默认值|null
     */
    public function input($name, $type = 'string')
    {
        if (is_array($name)) {
            return $this->inputData($name);
        }
        $val = $this->inputData([$name => $type]);
        return aval($val, $name);
    }

    /**
     * 过滤并返回外部传入数据
     * @param $k
     * @return array|mixed|string|null
     */
    protected function getInput(string $k)
    {
        $post = $this->request->getParsedBody();
        if (isset($post[$k])) {
            return $this->wordsFilter($post[$k]);
        }
        $get = $this->request->getQueryParams();
        if (isset($get[$k])) {
            return $this->wordsFilter($get[$k]);
        }
        return NULL;
    }

    /**
     * 违禁词处理
     * @param $word
     * @return array|mixed|string
     */
    protected function wordsFilter($word)
    {
        if (is_array($word)) {
            foreach ($word as $k => $v) {
                $word[$k] = $this->wordsFilter($v);
            }
        } else {
            $word = trim($word);
            foreach (explode('|', $this->cfg['notallowstr']) as $key => $val) {
                if (empty($val)) {
                    continue;
                }
                if (preg_match("/$val/i", $word)) {
                    throw new TextException(['code' => 21051, 'param' => ['title' => $val]]);
                }
            }
            foreach (explode('|', $this->cfg['replacestr']) as $key => $val) {
                if (empty($val)) {
                    continue;
                }
                if (preg_match("/$val/i", $word)) {
                    $word = str_replace($val, '***', $word);
                }
            }
        }
        return $word;
    }

    /**
     * 用户提交数据过滤
     * @param $string
     * @param null $flags
     * @return array|mixed|null|string|string[]
     */
    public function htmlspecialchars($string, $flags = null)
    {
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = $this->htmlspecialchars($val, $flags);
            }
        } else {
            if (empty($flags)) {
                $string = str_replace(['&', '"', '<', '>', '\'', '||', '*', '$', '(', ')'], ['&amp;', '&quot;', '&lt;', '&gt;', '&#039;', '&#124;&#124;', '&#042;', '&#036;', '&#040;', '&#041;'], $string);
                if (strpos($string, '&amp;#') !== false) {
                    $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
                }
            } elseif ($flags == 'en') {
                $string = str_replace(['&', '"', '<', '>', '\'', '||', '*', '$', '(', ')'], ['&amp;', '&quot;', '&lt;', '&gt;', '&#039;', '&#124;&#124;', '&#042;', '&#036;', '&#040;', '&#041;'], $string);
            } elseif ($flags == 'de') {
                $string = str_replace(['&#039;', '&#034;', '&#042;', '&quot;', '&ldquo;', '&rdquo;', '&amp;', '&#040;', '&#041;'], ["'", '"', '*', '"', '“', '”', '&', '(', ')'], $string);
            }
        }
        return $string;
    }

    /**
     * 对富文本进行转译
     * @param $string
     * @return array|string
     */
    private function addslashes($string)
    {
        if (is_array($string)) {
            $keys = array_keys($string);
            foreach ($keys as $key) {
                $string[addslashes($key)] = $this->addslashes($string[$key]);
            }
        } else {
            $string = addslashes($string);
        }
        return $string;
    }

    /**
     * 对转译过的富文本还原
     * @param $string
     * @return array|string
     */
    private static function stripslashes($string)
    {
        if (empty($string)) {
            return $string;
        }
        if (is_array($string)) {
            foreach ($string as $key => $val) {
                $string[$key] = self::stripslashes($val);
            }
        } else {
            $string = stripslashes($string);
        }
        return $string;
    }

    /**
     * 获取外部提交的数据
     * @param $param
     * @return array
     */
    protected function inputData(array $param): array
    {
        $data = [];
        foreach ($param as $k => $v) {
            $val = $this->getInput($k);
            if (!isset($val) && empty($_FILES[$k]['tmp_name'])) {
                continue;
            }
            if ($v == 'htmltext') {
                $data[$k] = $this->addslashes($val);
            } elseif ($v == 'string') {
                $data[$k] = $this->htmlspecialchars($val);
            } elseif ($v == 'float') {
                $data[$k] = (float)$val;
            } elseif ($v == 'price') {
                $data[$k] = round($val, 2);
            } elseif ($v == 'time') {
                $data[$k] = strtotime($val);
            } elseif (preg_match('/^img/i', $v)) {
                $width = $height = 0;
                if (strpos($v, ',')) {
                    list(, $width, $height) = explode(',', $v);
                }
                $uploadData = is_string($val) ? $val : ['files' => $_FILES[$k], 'width' => $width, 'height' => $height];
                $res = Upload::upload($uploadData);
                if ($res['code'] != 200 && $res['code'] != 23001) {
                    Output::showMsg($res);
                }
                $data[$k] = $res['data'] ?: '';
            } elseif (preg_match('/^int/i', $v)) {
                $data[$k] = (int)$val;
                if (strpos($v, ',')) {
                    list($v, $val1, $val2) = explode(',', $v);
                    if (strpos($v, '==')) {
                        $data[$k] = $val == str_replace('int==', '', $v) ? $val1 : $val2;
                    } else {
                        $data[$k] = $val ? $val1 : $val2;
                    }
                }
            } elseif (preg_match('/^isset/i', $v) && isset($_GET[$k])) {
                $data[$k] = self::htmlspecialchars($val);
                if (strpos($v, ',')) {
                    list($v, $val1, $val2) = explode(',', $v);
                    if (strpos($v, '==')) {
                        $data[$k] = $val == str_replace('isset==', '', $v) ? $val1 : $val2;
                    } else {
                        $data[$k] = $val ? $val1 : $val2;
                    }
                }
            } elseif (preg_match('/^checkbox/i', $v)) {
                if (strpos($v, ',')) {
                    list(, $func) = explode(',', $v);
                    $data[$k] = implode(',', array_map($func, $val));
                } else {
                    $data[$k] = self::htmlspecialchars(implode(',', $val));
                }
            } elseif ($v == 'serialize') {
                $data[$k] = $val ? serialize(self::htmlspecialchars($val)) : '';
            } elseif ($v == 'url') {
                $data[$k] = str_replace(['"', '<', '>', '\'', '(null)', '||', '*', '$', '(', ')'], ['&quot;', '&lt;', '&gt;', '&#039;', '', '&#124;&#124;', '&#042;', '&#036;', '&#040;', ' &#041;'], $val);
            } elseif ($v == 'tel') {
                $data[$k] = preg_replace('/[^\d\-]/i', '', $val);
            } elseif ($v == 'number') {
                $data[$k] = preg_replace('/[^\d]/i', '', $val);
            } elseif ($v == 'fnumber') {
                $data[$k] = preg_replace('/[^\d.]/i', '', $val);
            } elseif ($v == 'email') {
                $data[$k] = preg_replace('/[^\w\-.@]/i', '', $val);
            } elseif ($v == 'w') {
                $data[$k] = preg_replace('/[^\w\/]/i', '', $val);
            } elseif ($v == 'date') {
                $data[$k] = preg_replace('/[^\d\-: ]/i', '', $val);
            } elseif ($v == 'media' || $v == 'addon') {
                $uploadData = ['files' => $_FILES[$k], 'type' => $v];
                $res = Upload::upload($uploadData);
                if ($res['code'] != 200 && $res['code'] != 23001) {
                    Output::showMsg($res);
                }
                $data[$k] = $res['data'] ?: '';
            }
        }
        return $data;
    }
}
