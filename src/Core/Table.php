<?php

/**
 * DB层数据读写类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Container\ContainerInterface;
use SlimCMS\Error\TextException;
use SlimCMS\Helper\Str;
use SlimCMS\Interfaces\DatabaseInterface;
use App\Core\Redis;
use App\Core\Forms;

class Table
{

    /**
     * 表名
     * @var string
     */
    protected $tableName = '';

    /**
     * 数据库连接实例
     * @var mixed|DatabaseInterface
     */
    protected $db;

    /**
     * 某条数据缓存时间
     * @var int
     */
    protected $fetchTTL = 952000;

    /**
     * redis实例
     * @var \Redis|null
     *
     */
    protected $redis;

    /**
     * 查询条件
     * @var array
     */
    protected $where = '';

    /**
     * 查询条件是否纯数字,>0为对应数字
     * @var bool
     */
    protected $whereIsNumber = 0;

    /**
     * 联表查SQL
     * @var string
     */
    protected $join = '';

    /**
     * 查询数量
     * @var string
     */
    protected $limit = '';

    /**
     * 表名前缀
     * @var string
     */
    private $tablepre = '';

    /**
     * 排序
     * @var string
     */
    protected $orderby = ' order by main.id desc ';

    public function __construct(ContainerInterface $container, string $tableName)
    {
        $settings = $container->get('settings');
        $this->db = $container->get(DatabaseInterface::class);
        $this->tablepre = $settings['db']['tablepre'];
        $this->tableName = $this->tablepre . $tableName;
        $this->redis = $container->get(Redis::class);
    }

    /**
     * 数据库操作实例
     * @return mixed|DatabaseInterface
     */
    public function db()
    {
        return $this->db;
    }

    protected static function t(string $name = ''): Table
    {
        return Forms::t($name);
    }

    /**
     * 获取查询SQL
     * @param string $fields
     * @return string
     */
    private function selectSQL(string $fields): string
    {
        $sql = 'SELECT ' . $fields . ' FROM ' . $this->tableName . ' main ' .
            $this->join . $this->where . $this->orderby . $this->limit;
        return $sql;
    }

    /**
     * 数量统计
     * @param string $fields
     * @param int $cacheTime
     * @return int
     */
    public function count(string $fields = '*', int $cacheTime = 0): int
    {
        if ($this->redis->isAvailable()) {
            $cacheKey = $this->cacheKey(__FUNCTION__) . $this->md5key(func_get_args());
            $count = $cacheTime ? $this->redis->get($cacheKey) : 0;
        }
        if (empty($count)) {
            $fields = $fields ?: '*';
            $sql = $this->selectSQL('count(' . $fields . ')');
            $count = $this->db->fetchColumn($sql);
            $this->redis->isAvailable() && $cacheTime && $this->redis->set($cacheKey, $count, $cacheTime);
        }
        return (int)$count;
    }

    private function cacheKey($key): string
    {
        return $this->tableName . ':' . $key . ':';
    }


    /**
     * 清除fetch缓存
     * @param int $indexid
     * @return int|null
     */
    public function clearFetchCache(int $indexid)
    {
        $cachekey = $this->cacheKey($indexid);
        return $this->redis->del($cachekey);
    }

    /**
     * 获取某条记录
     * @param string $fields
     * @param int $cacheTime
     * @return array|bool|mixed|string|null
     */
    public function fetch(string $fields = '*', int $cacheTime = 0)
    {
        if ($this->redis->isAvailable() && !$this->join) {
            if ($this->whereIsNumber) {
                $indexid = $this->whereIsNumber;
            } else {
                $key = $this->cacheKey(__FUNCTION__) . $this->md5key();
                $indexid = $cacheTime ? $this->redis->get($key) : '';
                if (empty($indexid)) {
                    $sql = $this->selectSQL('id');
                    $indexid = $this->db->fetchColumn($sql);
                    $cacheTime && $this->redis->set($key, $indexid, $cacheTime);
                }
            }
            if (empty($indexid)) {
                return null;
            }
            $cachekey = $this->cacheKey($indexid);
            $data = $this->redis->get($cachekey);
            if (empty($data)) {
                $data = $this->db->fetch('SELECT * FROM ' . $this->tableName . ' WHERE id=' . $indexid);
                $this->fetchTTL && $this->redis->set($cachekey, $data, $this->fetchTTL);
            }
        } else {
            $sql = $this->selectSQL('*');
            $data = $this->db->fetch($sql);
        }

        if ($fields == '*') {
            return $data;
        }
        if (preg_match('/,/', $fields)) {
            $fields = explode(',', $fields);
            $row = [];
            foreach ($fields as $v) {
                $v = str_replace('main.', '', $v);
                if (preg_match('/ as /i', $v)) {
                    list($v, $v1) = explode(' as ', $v);
                    if (isset($data[$v])) {
                        $row[trim($v1)] = trim($data[$v]);
                    }
                } else {
                    if (isset($data[$v])) {
                        $row[$v] = $data[$v];
                    }
                }
            }
            return $row;
        }
        if (isset($data[$fields])) {
            return $data[$fields];
        }
    }

    /**
     * 列表数据
     * @param string $fields
     * @param string $indexField
     * @param int $cacheTime
     * @return array
     */
    public function fetchList(string $fields = '*', string $indexField = '', int $cacheTime = 0): array
    {
        $func = function ($fields, $indexField, $cacheTime) {
            if (preg_match('/distinct /i', $fields) || $this->join) {
                $sql = $this->selectSQL($fields);
                $list = $this->db->fetchList($sql);
            } else {
                $field1 = $cacheTime ? 'id' : (strpos($fields, ',') ? implode(',', $this->quoteField(explode(',', $fields))) : $fields);
                $sql = $this->selectSQL($field1);
                $list = $this->db->fetchList($sql, $indexField);
                if ($this->redis->isAvailable()) {
                    foreach ($list as $k => $v) {
                        $data = $this->withWhere($v['id'])->fetch($fields);
                        $list[$k] = is_array($data) ? $data : [$fields => $data];
                    }
                }
            }
            return $list;
        };
        if ($this->redis->isAvailable()) {
            if ($this->join) {
                $cacheTime = 0;
            }
            $cacheKey = $this->cacheKey(__FUNCTION__) . $this->md5key(func_get_args());
            $list = $cacheTime ? $this->redis->get($cacheKey) : [];
            if (empty($list)) {
                $list = $func($fields, $indexField, $cacheTime);
                $cacheTime && $this->redis->set($cacheKey, $list, $cacheTime);
            }
            return $list;
        }
        return $func($fields, $indexField, $cacheTime);

    }

    /**
     * 获取某一列数据
     * @param string $field
     * @param int $cacheTime
     * @return array
     */
    public function onefieldList(string $field = 'id', int $cacheTime = 0): array
    {
        $func = function ($field, $cacheTime) {
            $field1 = $cacheTime ? 'id' : $field;
            $sql = $this->selectSQL($field1);
            $list = $this->db->fetchList($sql);
            $arr = [];
            foreach ($list as $k => $v) {
                $arr[] = !empty($v['id']) ? $this->withWhere($v['id'])->fetch($field) : $v[$field];
            }
            return $arr;
        };
        if ($this->redis->isAvailable()) {
            if ($this->join) {
                $cacheTime = 0;
            }
            $cacheKey = $this->cacheKey(__FUNCTION__) . $this->md5key(func_get_args());
            $list = $cacheTime ? $this->redis->get($cacheKey) : [];
            if (empty($list)) {
                $list = $func($field, $cacheTime);
                $cacheTime && $this->redis->set($cacheKey, $list, $cacheTime);
            }
            return $list;
        }
        return $func($field, $cacheTime);
    }

    /**
     * 设置联表查SQL
     * @param $join
     * @return Table
     */
    public function withJoin(array $join): Table
    {
        if (!empty($join)) {
            $clone = clone $this;
            $clone->join = ' left join ' . $this->tablepre . implode(' left join ' . $this->tablepre, $join);
            return $clone;
        }
        return $this;
    }

    /**
     * 设置读取数据数量
     * @param $limit
     * @return Table
     */
    public function withLimit($limit): Table
    {
        if (!empty($limit)) {
            $clone = clone $this;
            $clone->limit = strpos((string)$limit, 'limit') !== false ? ' ' . $limit : ' limit ' . $limit;
            return $clone;
        }
        return $this;
    }

    /**
     * 设置排序
     * @param string $order
     * @param string $way
     * @return Table
     */
    public function withOrderby(string $order = '', string $way = 'desc'): Table
    {
        $clone = clone $this;
        $order = $order ?: 'main.id';
        if (!preg_match('/^group by /i', $order)) {
            $order = ' order by ' . $order;
        }
        $way = $way == 'asc' ? 'asc' : 'desc';
        $clone->orderby = ' ' . $order . ' ' . $way;
        return $clone;
    }

    /**
     * 更新fetch缓存
     * @param int $id
     * @param array $data
     */
    private function updateFetchCache(int $id, array $data)
    {
        if ($this->redis->isAvailable()) {
            $cachekey = $this->cacheKey($id);
            $cacheData = $this->redis->get($cachekey);
            if ($cacheData) {
                foreach ($data as $key => $value) {
                    $matches = [];
                    preg_match('/^(#@#){1}[A-Za-z]{2,}([\w])*(\+|\-)([\d.]{1,20})$/i', (string)$value, $matches);
                    if ($matches) {
                        if ($matches[3] == '+') {
                            $data[$key] = $cacheData[$key] + (int)$matches[4];
                        } elseif ($matches[3] == '-') {
                            $data[$key] = $cacheData[$key] - (int)$matches[4];
                        }
                    }
                }
                $cacheData = array_merge($cacheData, $data);
                $this->redis->set($cachekey, $cacheData, $this->fetchTTL);
            }
        }
    }

    /**
     * 修改操作
     * @param array $data
     * @return int
     */
    public function update(array $data): int
    {
        if ($this->where && !empty($data)) {
            if ($this->redis->isAvailable()) {
                $row = $this->fetchList('id');
                foreach ($row as $v) {
                    $this->updateFetchCache((int)$v['id'], $data);
                }
            }
            $sql = 'UPDATE ' . $this->tableName . ' SET ' . $this->implodeSave($data) . $this->where;
            $query = $this->db->query($sql);
            return $this->db->affectedRows($query);
        }
        return 0;
    }

    /**
     * 删除操作
     * @return int
     */
    public function delete(): int
    {
        if ($this->where) {
            if ($this->redis->isAvailable()) {
                $row = $this->fetchList('id');
                foreach ($row as $v) {
                    $cachekey = $this->cacheKey($v['id']);
                    $this->redis->del($cachekey);
                }
            }
            $query = $this->db->query('DELETE FROM ' . $this->tableName . $this->where);
            return $this->db->affectedRows($query);
        }
        return 0;
    }

    /**
     * 插入数据
     * @param array $data
     * @param bool $returnID
     * @param bool $replace
     * @return int
     */
    public function insert(array $data, bool $returnID = false, bool $replace = false): int
    {
        $sql = $this->implodeSave($data);
        $cmd = $replace ? 'REPLACE INTO ' : 'INSERT INTO ';
        $query = $this->db->query($cmd . $this->tableName . ' set ' . $sql);
        if ($returnID) {
            return (int)$this->db->insertId();
        }
        return $this->db->affectedRows($query);
    }

    /**
     * 条件处理
     * @param $val
     * @return Table
     * @throws TextException
     */
    public function withWhere($val): Table
    {
        if (empty($val)) {
            $where = '';
        } elseif (is_array($val)) {
            $where = $this->implode($val, 'and');
        } elseif (is_numeric($val)) {
            $this->whereIsNumber = $val;
            $where = $this->field('id', $val);
        } else {
            $where = str_replace(' where ', '', $val);
        }
        $clone = clone $this;
        $clone->where = $where ? ' where ' . $where : '';
        return $clone;
    }

    private function implode(array $array, string $glue = ','): string
    {
        $sql = $comma = '';
        $glue = ' ' . trim($glue) . ' ';
        foreach ($array as $k => $v) {
            if (is_numeric($k)) {
                $sql .= $comma . $v;
            } elseif (is_array($v)) {
                $sql .= $comma . $this->field($this->quoteField($k), $v);
            } else {
                $sql .= $comma . $this->quoteField($k) . '=' . $this->quote($v);
            }
            $comma = $glue;
        }
        return $sql;
    }

    private function quote($str, $noarray = false)
    {
        if (is_string($str)) {
            if (preg_match('/^(#@#){1}[A-Za-z]{2,}([\w])*(\+|\-)([\d.]{1,20})$/i', $str)) {
                return addcslashes(preg_replace('/^#@#/i', '', $str), "\n\r\\'\"\032");
            }
            return '\'' . addcslashes($str, "\n\r\\'\"\032") . '\'';
        }

        if (is_int($str) or is_float($str)) {
            return '\'' . $str . '\'';
        }

        if (is_array($str)) {
            if ($noarray === false) {
                foreach ($str as &$v) {
                    $v = $this->quote($v, true);
                }
                return $str;
            }
            return '\'\'';
        }

        if (is_bool($str)) {
            return $str ? '1' : '0';
        }
        return '\'\'';
    }

    private function quoteField($field)
    {
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $field[$k] = $this->quoteField($v);
            }
        } else {
            if (strpos($field, '`') !== false) {
                $field = str_replace('`', '', $field);
            }
            if (preg_match('/concat/i', $field)) {
                return $field;
            }
            if (preg_match('/\./', $field)) {
                list($pre, $field) = explode('.', $field);
                $field = trim($field);
                if (strpos($field, ' ')) {
                    $field = $pre . '.`' . strstr($field, ' ', true) . '`' . strstr($field, ' ');
                } else {
                    $field = $pre . '.`' . $field . '`';
                }
            } else {
                $field = '`' . $field . '`';
            }
        }
        return $field;
    }

    public function field($field, $val, $glue = '=')
    {
        $field = $this->quoteField($field);
        if (empty($val) && is_array($val)) {
            $val = '';
        }
        if (is_array($val)) {
            $glue = $glue == 'notin' ? 'notin' : 'in';
        } elseif ($glue == 'in') {
            $glue = '=';
        }

        switch ($glue) {
            case '=':
                return $field . $glue . $this->quote($val);
            case '-':
            case '+':
                return $field . '=' . $field . $glue . $this->quote((string)$val);
            case '|':
            case '&':
            case '^':
                return $field . '=' . $field . $glue . $this->quote($val);
            case '>':
            case '<':
            case '<>':
            case '<=':
            case '>=':
                return $field . $glue . $this->quote($val);
            case 'unlike':
            case 'like':
                $not = $glue == 'unlike' ? ' not ' : '';
                if (preg_match('/%/', $val)) {
                    return $field . $not . ' LIKE(' . $this->quote($val) . ')';
                }
                return $field . $not . ' LIKE(' . $this->quote('%' . $val . '%') . ')';
            case 'in':
            case 'notin':
                $val = $val ? implode(',', $this->quote($val)) : '\'\'';
                return $field . ($glue == 'notin' ? ' NOT' : '') . ' IN(' . $val . ')';
            case 'find':
                return 'FIND_IN_SET(' . $this->quote($val) . ', ' . $field . ')>0';
            case 'nofind':
                return 'FIND_IN_SET(' . $this->quote($val) . ', ' . $field . ')<1';
            case 'between':
                list($min, $max) = explode(',', $val);
                $min = preg_replace('/[^\d.-]/', '', $min);
                $max = preg_replace('/[^\d.-]/', '', $max);
                return '(' . $field . ' between  ' . $min . ' and ' . $max . ')';
            case 'regexp':
                return $field . ' REGEXP ' . $this->quote($val);
            default:
                throw new TextException(21058, '', 'SQL');
        }
    }

    /**
     * 增改用到
     * @param array $array
     * @param string $glue
     * @return string
     */
    private function implodeSave(array $array, string $glue = ','): string
    {
        $sql = $comma = '';
        $glue = ' ' . trim($glue) . ' ';
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $v = serialize($v);
            }
            $sql .= $comma . $this->quoteField($k) . '=' . $this->quote($v);
            $comma = $glue;
        }
        return $sql;
    }

    /**
     * 某表的表单结构
     * @return array
     */
    public function fetchAllField(): array
    {
        return $this->db->fetchList('SHOW FIELDS FROM ' . $this->tableName, 'Field');
    }

    /**
     * 用于编辑添加模型时创建相应字段
     * @param array $data
     * @return int
     */
    public function fieldUpdate(array $data): int
    {
        $identifier = $data['identifier'];
        $datatype = $data['datatype'];
        $fields = $this->fetchAllField();
        $length = !empty($data['fieldlength']) ? str_replace('.', ',', $data['fieldlength']) : '';
        if (!empty($fields[$identifier])) {
            $sql = 'ALTER TABLE  `' . $this->tableName . '` MODIFY COLUMN `' . $identifier . '` ';
        } else {
            $sql = 'ALTER TABLE  `' . $this->tableName . '` ADD `' . $identifier . '` ';
        }
        if (in_array($datatype, ['multitext', 'multidate', 'htmltext', 'imgs', 'serialize'])) {
            $fieldtype = !empty($data['fieldtype']) ? $data['fieldtype'] : 'TEXT';
            $sql .= $fieldtype . ' NOT NULL ';
        } elseif ($datatype == 'int' || $datatype == 'datetime' || $datatype == 'date' || $datatype == 'stepselect') {
            $fieldtype = !empty($data['fieldtype']) ? $data['fieldtype'] : 'INT';
            $length = $length ?: '11';
            $default = !empty($data['default']) ? $data['default'] : 0;
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL DEFAULT  \'' . $default . '\' ';
        } elseif ($datatype == 'float') {
            $fieldtype = !empty($data['fieldtype']) ? $data['fieldtype'] : 'double';
            $length = $length ?: '15,4';
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL ';
        } elseif ($datatype == 'price') {
            $fieldtype = !empty($data['fieldtype']) ? $data['fieldtype'] : 'decimal';
            $length = $length ?: '15,2';
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL ';
        } elseif ($datatype == 'hidden') {
            $fieldtype = !empty($data['fieldtype']) ? $data['fieldtype'] : 'VARCHAR';
            $length = $length ?: '250';
            $default = !empty($data['default']) ? ' DEFAULT  \'' . $data['default'] . '\' ' : '';
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL ' . $default;
        } else {
            $fieldtype = !empty($data['fieldtype']) ? $data['fieldtype'] : 'VARCHAR';
            $length = $length ?: '250';
            $default = !empty($data['default']) ? $data['default'] : (strpos($fieldtype, 'int') !== false ? '0' : '');
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL DEFAULT  \'' . $default . '\' ';
        }

        //生成字段注释
        $comment = $data['title'];
        if (!empty($data['rules'])) {
            $arr = [];
            foreach (unserialize($data['rules']) as $k1 => $v1) {
                $arr[] = $k1 . '=' . $v1;
            }
            $comment .= '(' . implode(',', $arr) . ')';
        }
        $comment = mb_substr($comment, 0, 255, 'utf-8');
        $query = $this->db->query($sql . ' COMMENT \'' . $comment . '\'');

        if (empty($fields[$identifier]['Key']) && aval($data, 'search') == 1) {
            $query = $this->db->query('ALTER TABLE  `' . $this->tableName . '` ADD INDEX (`' . $identifier . '`)');
        }
        if (!empty($fields[$identifier]['Key']) && aval($data, 'search') == 2) {
            $query = $this->db->query('ALTER TABLE  `' . $this->tableName . '` DROP INDEX `' . $identifier . '`');
        }
        return $this->db->affectedRows($query);
    }

    /**
     * 删除字段
     * @param string $identifier
     * @return int
     */
    public function fieldDelete(string $identifier): int
    {
        $fields = $this->fetchAllField();
        if (!empty($fields[$identifier])) {
            $query = $this->db->query('ALTER TABLE  `' . $this->tableName . '` DROP `' . $identifier . '`');
            return $this->db->affectedRows($query);
        }
        return 0;
    }

    /**
     * 某字段数量统计
     * @param string $field
     * @return string
     */
    public function sum(string $field)
    {
        $sql = $this->selectSQL('sum(' . $field . ')');
        return $this->db->fetchColumn($sql);
    }

    /**
     * 返回分页列表数据
     * @param int $page
     * @param string $fields
     * @param int $pagesize
     * @param int $cacheTime
     * @param string $indexField
     * @return array
     */
    public function pageList(int $page = 1, string $fields = '*', int $pagesize = 30, int $cacheTime = 0, string $indexField = ''): array
    {
        $page = max(1, $page);
        $field = $fields ?: '*';
        $count = $this->count('*', $cacheTime);
        $maxpages = (int)ceil($count / $pagesize);
        $page = $page > $maxpages ? $maxpages : $page;
        $start = ($page - 1) * $pagesize;
        $this->limit = ' limit ' . $start . ',' . $pagesize;

        if (empty($count)) {
            $list = [];
        } else {
            $list = $this->fetchList($fields, $indexField, $cacheTime);
        }
        return ['list' => $list, 'count' => $count, 'maxpages' => $maxpages, 'page' => $page, 'pagesize' => $pagesize];
    }


    /**
     * 将缓存KEY中含有的时间戳后3位改成000，否则缓存会一直生成，失去缓存意义
     */
    protected function md5key(array $condition = []): string
    {
        $condition[] = $this->where;
        $condition[] = $this->join;
        return Str::md5key($condition);
    }
}
