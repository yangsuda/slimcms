<?php
/**
 * 文件缓存类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Helper;

class FileCache
{
    private static function getCacheFile($key)
    {
        $dir = CSDATA . 'fileCache/' . $key{0}.'/';
        File::mkdir($dir);
        return $dir . $key . '.txt';
    }

    /**
     * 保存缓存数据
     * @param $key
     * @param $value
     * @param $ttl
     * @return bool
     */
    public static function set($key, $value, $ttl)
    {
        $cacheFile = self::getCacheFile($key);
        $data = [];
        $data['value'] = $value;
        $data['timestamp'] = TIMESTAMP + $ttl;
        $str = json_encode($data);
        file_put_contents($cacheFile, $str);
        return true;
    }

    /**
     * 获取缓存数据
     * @param $key
     * @return |null
     */
    public static function get($key)
    {
        $cacheFile = self::getCacheFile($key);
        if (!is_file($cacheFile)) {
            return null;
        }
        $str = file_get_contents($cacheFile);
        $data = json_decode($str, true);
        if ($data['timestamp'] < TIMESTAMP) {
            return null;
        }
        return $data['value'];
    }
}
