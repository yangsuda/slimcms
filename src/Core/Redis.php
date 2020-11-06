<?php
/**
 * redis类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Container\ContainerInterface;
use SlimCMS\Error\TextException;

class Redis
{
    public static $redis;
    private $setting;

    public function __construct(ContainerInterface $container)
    {
        if (empty(self::$redis)) {
            $this->setting = $container->get('settings');
            $config = &$this->setting['redis'];
            if (!empty($config['server'])) {
                try {
                    $redis = new \Redis();
                    if (!empty($config['pconnect'])) {
                        $connect = @$redis->pconnect($config['server'], aval($config, 'port'));
                    } else {
                        $connect = @$redis->connect($config['server'], aval($config, 'port'));
                    }
                    if ($connect) {
                        $redis->auth(aval($config, 'password'));
                        //0 值不序列化保存，1反之(序列化可以存储对象)
                        $redis->setOption(\Redis::OPT_SERIALIZER, 0);
                        $redis->select(1);
                        self::$redis = &$redis;
                    }
                } catch (RedisException $e) {
                    throw new TextException(21057, $e->getMessage(), 'redis');
                }
            }
        }
    }

    /**
     * 设置存储库
     * @param int $dbindex
     * @return $this|null
     */
    public function selectDB(int $dbindex = 1)
    {
        if (empty(self::$redis)) {
            return null;
        }
        self::$redis->select($dbindex);
        return $this;
    }

    private function cacheKey(&$key)
    {
        $key = $this->setting['redis']['prefix'] . $key;
    }

    public function info()
    {
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->info();
    }

    public function get($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $data = self::$redis->get($key);
        if (is_numeric($data)) {
            return $data;
        }
        return $data ? unserialize($data) : $data;
    }

    public function set($key, $data, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        if (!empty($data)) {
            if (!is_numeric($data)) {
                $data = serialize($data);
            }
            self::$redis->set($key, $data, $ttl);
            return true;
        }
        return $this->del($key);
    }

    /**
     * 指定的 key 不存在时，才为 key 设置指定的值
     * @param unknown_type $key
     * @param unknown_type $data
     * @param unknown_type $ttl
     */
    public function setnx($key, $data, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        if (!empty($data)) {
            if (!is_numeric($data)) {
                $data = serialize($data);
            }
            $res = self::$redis->setnx($key, $data);
            if ($res && $ttl) {
                self::$redis->expire($key, $ttl);
            }
            return $res;
        }
        return $this->del($key);
    }

    public function incr($key, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->incr($key);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    public function decr($key, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->decr($key);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    public function decrby($key, $num = 1, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->decrBy($key, $num);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    public function incrby($key, $num = 1, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->incrby($key, $num);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    public function exists($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->exists($key);
    }

    public function expire($key, $ttl)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->expire($key, $ttl);
    }

    public function del($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->del($key);
    }

    public function hmset($key, $data, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        self::$redis->hmset($key, $data);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    public function hmget($key, $hashKeys)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->hMGet($key, $hashKeys);
    }

    public function hset($key, $field, $data, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        self::$redis->hset($key, $field, $data);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    public function hsetnx($key, $field, $data, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->hsetnx($key, $field, $data);
        if ($res && $ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    public function hget($key, $field)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->hget($key, $field);
    }

    public function hincrby($key, $field, $num = 1, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->hincrby($key, $field, $num);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    public function hexists($key, $field)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->hexists($key, $field);
    }

    public function hlen($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->hlen($key);
    }

    public function hgetall($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->hgetall($key);
    }

    public function hdel($key, $fields)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $keys = is_array($fields) ? implode('\',\'', $fields) : $fields;
        eval("self::\$redis->hdel(\$key,'" . $keys . "');");
    }

    public function zadd($key, $score, $member, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        self::$redis->zadd($key, $score, $member);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    /**
     * 有序集合中对指定成员的分数加上增量 increment
     * @param $key
     * @param $value
     * @param $member
     * @param int $ttl
     * @return bool
     */
    public function zincrby($key, $value, $member, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        self::$redis->zIncrBy($key, $value, $member);
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    public function zrem($key, $members)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $glue = '';
        //数据库中数据保存的数据是xx,xx,xx形式的，会被拆分保存，因集合成员只能唯一，所以以空格区分的，删除时也要同时删除，默认
        for ($i = 0; $i < 50; $i++) {
            $keys = is_array($members) ? implode($glue . '\',\'', $members) : $members;
            eval("\$res = self::\$redis->zrem(\$key,'" . $keys . $glue . "');");
            $glue .= ' ';
            if (empty($res)) {
                break;
            }
        }
    }

    /**
     * 移除有序集合中给定的分数区间的所有成员
     * @param $key
     * @param $start
     * @param $end
     * @return int
     */
    public function zremrangebyscore($key, $start, $end)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zRemRangeByScore($key, $start, $end);
    }

    /**
     * 获取有序集合的成员数
     * @param $key
     * @return int
     */
    public function zcard($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zCard($key);
    }

    public function zrange($key, $start, $end, $withscores = null)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zRange($key, $start, $end, $withscores);
    }

    /**
     * 读取指定范围从大小到的数据
     * @param $key
     * @param $start
     * @param $end
     * @param null $withscores true返回分数值
     * @return array
     */
    public function zrevrange($key, $start, $end, $withscores = null)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zRevRange($key, $start, $end, $withscores);
    }

    /**
     * 返回有序集合中指定成员的排名，有序集成员按分数值递减(从大到小)排序
     * @param $key
     * @param $member
     * @return int
     */
    public function zrevRank($key, $member)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zRevRank($key, $member);
    }

    /**
     * 返回有序集合中指定成员的索引
     * @param $key
     * @param $member
     * @return int
     */
    public function zrank($key, $member)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zrank($key, $member);
    }

    /**
     * 返回有序集中，成员的分数值
     * @param $key
     * @param $member
     * @return float
     */
    public function zscore($key, $member)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zScore($key, $member);
    }

    /**
     * 计算在有序集合中指定区间分数的成员数
     * @param $key
     * @param $start
     * @param $end
     * @return int
     */
    public function zcount($key, $start, $end)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->zCount($key, $start, $end);
    }

    public function ttl($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->ttl($key);
    }

    /**
     * 向集合添加一个或多个成员
     * @param $key
     * @param $member
     * @param int $ttl
     * @return bool
     */
    public function sadd($key, $member, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        if (is_array($member)) {
            //一次写太多程序要挂掉，只能分段写
            foreach ($this->yield_slice($member) as $slice) {
                if (empty($slice)) {
                    break;
                }
                $members = str_replace(' ', '', implode('\',\'', $slice));
                eval("self::\$redis->sadd(\$key,'" . $members . "');");
            }
            unset($member);
        } else {
            self::$redis->sadd($key, $member);
        }
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
        return true;
    }

    public function srem($key, $member)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        if (is_array($member)) {
            //一次写太多程序要挂掉，只能分段写
            foreach ($this->yield_slice($member) as $slice) {
                if (empty($slice)) {
                    break;
                }
                eval("self::\$redis->srem(\$key,'" . implode('\',\'', $slice) . "');");
            }
            unset($member);
        } else {
            self::$redis->srem($key, $member);
        }
        return true;
    }

    /**
     * 获取集合的成员数
     * @param $key
     * @return int
     */
    public function scard($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->scard($key);
    }

    public function sexists($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->scard($key);
        if (empty($res)) {
            return false;
        }
        if ($res == 1) {
            $res = self::$redis->sinter($key);
            if (empty($res[0])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 返回集合中的所有成员
     * @param $key
     * @return array
     */
    public function smembers($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->sMembers($key);
    }

    /**
     * 判断 member 元素是否是集合 key 的成员
     * @param $key
     * @param $val
     * @return bool
     */
    public function sismember($key, $val)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->sismember($key, $val);
    }

    /**
     * 将一个或多个值插入到列表头部
     * @param $key
     * @param $value
     * @param int $ttl
     */
    public function lpush($key, $value, $ttl = 5184000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $value = is_array($value) ? implode('\',\'', $value) : $value;
        eval("\$res = self::\$redis->lpush(\$key,'" . $value . "');");
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
    }

    /**
     * 取出列表集中指定长度数据
     * @param $key
     * @param int $stat
     * @param string $end
     * @return array
     */
    public function lrange($key, $stat = 0, $end = '-1')
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->lrange($key, $stat, $end);
    }

    /**
     * 向列表末尾插入元素
     * @param $key
     * @param $value
     * @param int $ttl
     */
    public function rpush($key, $value, $ttl = 5184000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $value = is_array($value) ? implode('\',\'', $value) : $value;
        eval("\$res = self::\$redis->rpush(\$key,'" . $value . "');");
        if ($ttl) {
            self::$redis->expire($key, $ttl);
        }
    }

    /**
     * 取出列表中的元素
     * @param $key
     * @param int $index
     * @return mixed
     */
    public function lindex($key, $index = 0)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->lindex($key, $index);
    }

    /**
     * 获取列表长度
     * @param $key
     * @return int
     */
    public function llen($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->llen($key);
    }

    /**
     * 去除列表的第一个元素
     * @param $key
     * @return string
     */
    public function lpop($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->lpop($key);
    }

    /**
     * 去除列表的某个元素
     * @param $key
     * @param $value
     * @param $count
     * @return int
     */
    public function lrem($key, $value, $count = 0)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->lrem($key, $value, $count);
    }

    /**
     * 查找符合给定模式的key
     * @param $key
     * @return array
     */
    public function keys($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->keys($key);
    }

    /**
     * 批量删除相关key
     * @param $key
     * @return bool
     */
    public function delKeys($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        if ($key == '*') {
            return false;
        }
        $res = self::$redis->keys($key);
        foreach ($res as $v) {
            self::$redis->del($v);
        }
        return true;
    }

    /**
     * 用scan达到keys的效果
     * @param $pattern
     * @return array
     */
    public function scan($pattern)
    {
        if (empty(self::$redis)) {
            return null;
        }
        self::$redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
        $iterator = null;
        $key_array = array();
        while ($keys = self::$redis->scan($iterator, $pattern)) {
            $key_array = array_merge($key_array, $keys);
        }
        return $key_array;
    }

    /**
     * 用hscan达到hgetall的效果
     * @param $key
     * @return array
     */
    public function hscan($key)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        self::$redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
        $iterator = null;
        $key_array = array();
        while ($keys = self::$redis->hScan($key, $iterator)) {
            $key_array += $keys;
        }
        return $key_array;
    }

    /**
     * 随机返回set中的item
     * @param $key
     * @param null $count
     * @return array|string
     */
    public function sRandMember($key, $count = null)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->sRandMember($key, $count);
    }

    /**
     * 清空所有的key，必须谨慎操作
     * @return bool|null
     */
    public function flushall()
    {
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->flushall();
    }


    /**
     * 添加元素信息
     * @param string $key 键名
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @param string $member 元素名称
     * @param int $ttl 有效期，秒
     * @return int 当前元素数量
     */
    public function geoAdd($key, $longitude, $latitude, $member, $ttl = 12960000)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        $res = self::$redis->geoadd($key, $longitude, $latitude, $member);
        if ($res && $ttl) {
            self::$redis->expire($key, $ttl);
        }
        return $res;
    }

    /**
     * 返回两点之间的距离
     * @param string $key 键名
     * @param string $member1 元素1
     * @param string $member2 元素2
     * @param null $unit 距离单位。m 表示单位为米。km 表示单位为千米。mi 表示单位为英里。ft 表示单位为英尺。
     * @return float 距离
     */
    public function geoDist($key, $member1, $member2, $unit)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->geodist($key, $member1, $member2, $unit);
    }

    /**
     * 返回元素的经纬度
     * @param string $key 键名
     * @param string $member 元素
     * @return array 经纬度值数组
     */
    public function geoPos($key, $member)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->geopos($key, $member);
    }

    /**
     * 以一个位置画圆，返回指定距离内所有的元素
     * @param string $key 键名
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @param float $radius 距离
     * @param string $unit 距离单位，m 表示单位为米。km 表示单位为千米。mi 表示单位为英里。ft 表示单位为英尺
     * @param array $options 额外信息数组
     * |Key         |Value          |Description                                 |
     * |------------|---------------|------------------------------------------- |
     * |COUNT       |integer > 0    |返回元素个数                                  |
     * |            |WITHCOORD      |将位置元素的经度和维度一并返回                   |
     * |            |WITHDIST       |将位置元素与中心之间的距离一并返回                |
     * |            |WITHHASH       |返回位置元素经过原始 geohash 编码的有序集合分值    |
     * |            |ASC            |按照从近到远的方式返回位置元素                   |
     * |            |DESC           |按照从远到近的方式返回位置元素                   |
     * |STORE       |key            |将返回结果的地理位置信息保存到指定键              |
     * |STOREDIST   |key            |将返回结果距离中心节点的距离保存到指定键           |
     *
     * @return mixed
     */
    public function geoRadius($key, $longitude, $latitude, $radius, $unit, $options)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->georadius($key, $longitude, $latitude, $radius, $unit, $options);
    }

    /**
     * 找出位于指定范围内的元素，中心点是由给定的位置元素决定的。
     * @param string $key 键名
     * @param string $member 元素
     * @param float $radius 距离
     * @param string $units 距离单位，m 表示单位为米。km 表示单位为千米。mi 表示单位为英里。ft 表示单位为英尺
     * @param array $options 额外信息数组
     * |Key         |Value          |Description                                 |
     * |------------|---------------|------------------------------------------- |
     * |COUNT       |integer > 0    |返回元素个数                                  |
     * |            |WITHCOORD      |将位置元素的经度和维度一并返回                   |
     * |            |WITHDIST       |将位置元素与中心之间的距离一并返回                |
     * |            |WITHHASH       |返回位置元素经过原始 geohash 编码的有序集合分值    |
     * |            |ASC            |按照从近到远的方式返回位置元素                   |
     * |            |DESC           |按照从远到近的方式返回位置元素                   |
     * |STORE       |key            |将返回结果的地理位置信息保存到指定键              |
     * |STOREDIST   |key            |将返回结果距离中心节点的距离保存到指定键           |
     * @return array
     */
    public function geoRadiusByMember($key, $member, $radius, $units, $options)
    {
        $this->cacheKey($key);
        if (empty(self::$redis)) {
            return null;
        }
        return self::$redis->georadiusbymember($key, $member, $radius, $units, $options);
    }
}