<?php

/**
 * 文件、文件夹操作类
 * @author zhucy
 * @date 2019.09.18
 */

namespace SlimCMS\Helper;

class File
{

    /**
     * 生成文件夹
     * @param $dir
     * @param int $mode
     * @param bool $makeindex
     * @return bool
     */
    public static function mkdir($dir, $mode = 0777, $makeindex = TRUE)
    {
        if (!is_dir($dir)) {
            self::mkdir(dirname($dir), $mode, $makeindex);
            @mkdir($dir, $mode);
            if (!empty($makeindex)) {
                @touch($dir . '/index.html');
                @chmod($dir . '/index.html', 0777);
            }
        }
        return true;
    }

    /**
     * 日志记录
     * @param $msg
     * @param string $dir
     * @param string $format
     */
    public static function log($msg, $dir = 'log',$format='Y-m-d')
    {
        $path = CSDATA . '/' . $dir . '/' . date('Y') . '/';
        self::mkdir($path);
        $logurl = $path . date($format,TIMESTAMP) . '.php';
        !is_file($logurl) && @file_put_contents ( $logurl, "<?php\n exit();\n?>\n", FILE_APPEND );
        file_put_contents ( $logurl,  date('Y-m-d H:i:s') . ":\n" . $msg . "\n", FILE_APPEND );
    }
}
