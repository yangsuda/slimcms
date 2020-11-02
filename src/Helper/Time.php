<?php

/**
 * 时间处理类
 * @author zhucy
 */

namespace SlimCMS\Helper;

class Time
{

    public static function gmdate($timestamp, $format = 'd', $timeoffset = '9999', $uformat = '')
    {
        static $dformat, $tformat, $dtformat, $offset, $lang;
        if ($dformat === null) {
            $dformat = 'Y-m-d';
            $tformat = 'H:i';
            $dtformat = $dformat . ' ' . $tformat;
            $offset = 8;
            $lang = array(
                'before' => '前',
                'day' => '天',
                'yday' => '昨天',
                'byday' => '前天',
                'hour' => '小时',
                'half' => '半',
                'min' => '分钟',
                'sec' => '秒',
                'now' => '刚刚',
            );
        }
        $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
        $timestamp += $timeoffset * 3600;
        $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
        if ($format == 'u') {
            $todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
            $s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
            $time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
            if ($timestamp >= $todaytimestamp) {
                if ($time > 3600) {
                    return '<span title="' . $s . '">' . intval($time / 3600) . '&nbsp;' . $lang['hour'] . $lang['before'] . '</span>';
                } elseif ($time > 1800) {
                    return '<span title="' . $s . '">' . $lang['half'] . $lang['hour'] . $lang['before'] . '</span>';
                } elseif ($time > 60) {
                    return '<span title="' . $s . '">' . intval($time / 60) . '&nbsp;' . $lang['min'] . $lang['before'] . '</span>';
                } elseif ($time > 0) {
                    return '<span title="' . $s . '">' . $time . '&nbsp;' . $lang['sec'] . $lang['before'] . '</span>';
                } elseif ($time == 0) {
                    return '<span title="' . $s . '">' . $lang['now'] . '</span>';
                } else {
                    return '<span title="' . $s . '">' . gmdate($dformat, $timestamp) . '</span>';
                }
            } elseif (($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
                if ($days == 0) {
                    return '<span title="' . $s . '">' . $lang['yday'] . '&nbsp;' . gmdate($tformat, $timestamp) . '</span>';
                } elseif ($days == 1) {
                    return '<span title="' . $s . '">' . $lang['byday'] . '&nbsp;' . gmdate($tformat, $timestamp) . '</span>';
                } else {
                    return '<span title="' . $s . '">' . ($days + 1) . '&nbsp;' . $lang['day'] . $lang['before'] . '</span>';
                }
            } else {
                return '<span title="' . $s . '">' . gmdate($dformat, $timestamp) . '</span>';
            }
        } else {
            return gmdate($format, $timestamp);
        }
    }

    public static function getWeek($time = '')
    {
        $time = $time > 0 ? $time : TIMESTAMP;
        $arr = array('日', '一', '二', '三', '四', '五', '六');
        return $arr[date('w', $time)];
    }
}
