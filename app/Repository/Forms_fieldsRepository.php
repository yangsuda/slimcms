<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\Forms_fieldsEntity;
use SlimCMS\Abstracts\RepositoryAbstract;

class Forms_fieldsRepository extends RepositoryAbstract
{
    public function fetch(string $field, int $cacheTime = 0): ?Forms_fieldsEntity
    {
        $data = parent::fetch($field, $cacheTime);
        return $data ? Forms_fieldsEntity::fromArray($data) : null;
    }

    public function fetchList(string $field, string $indexField = '', int $cacheTime = 0): array
    {
        $data = parent::fetchList($field, $indexField, $cacheTime);
        return Forms_fieldsEntity::fromArrayList($data);
    }

    public function list(string $fields = 'id,createtime', int $page = 1, int $pagesize = 30): array
    {
        $data = parent::list($fields, $page, $pagesize);
        $data['list'] = Forms_fieldsEntity::fromArrayList($data['list']);
        return $data;
    }

    /**
     * 某表的表单结构
     * @return array
     */
    private function fetchAllField(string $table): array
    {
        // [SQL安全改造] 表名白名单校验，防止SHOW FIELDS注入
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }
        return $this->t()->db()->fetchList('SHOW FIELDS FROM ' . $this->setting['db']['tablepre'] . $table, 'Field');
    }

    /**
     * 更新字段
     * @param string $table
     * @param Forms_fieldsEntity $data
     * @return int
     */
    public function fieldUpdate(string $table, Forms_fieldsEntity $data): int
    {
        // [SQL安全改造] 表名与字段名白名单校验，防止ALTER注入
        if (empty($table) || empty($data) || !preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', (string)$data->identifier)) {
            return 0;
        }
        $fields = $this->fetchAllField($table);
        $tableName = $this->setting['db']['tablepre'] . $table;
        $length = $data->fieldlength ? str_replace('.', ',', (string)$data->fieldlength) : '';
        // [SQL安全改造] 字段长度仅允许数字和逗号
        if ($length && !preg_match('/^[\d,]+$/', $length)) {
            $length = '';
        }
        // [SQL安全改造] 字段类型白名单校验
        $fieldtype = (string)$data->fieldtype;
        if ($fieldtype && !preg_match('/^[a-zA-Z0-9_ ]+$/', $fieldtype)) {
            $fieldtype = '';
        }
        if (!empty($fields[$data->identifier])) {
            $sql = 'ALTER TABLE  `' . $tableName . '` MODIFY COLUMN `' . $data->identifier . '` ';
        } else {
            $sql = 'ALTER TABLE  `' . $tableName . '` ADD `' . $data->identifier . '` ';
        }
        if (in_array($data->datatype, ['multitext', 'multidate', 'htmltext', 'imgs', 'serialize', 'addons'])) {
            $fieldtype = $fieldtype ?: 'TEXT';
            $sql .= $fieldtype . ' NOT NULL ';
        } elseif (in_array($data->datatype, ['int', 'datetime', 'date', 'stepselect'])) {
            $fieldtype = $fieldtype ?: 'bigint';
            $length = $length ?: '11';
            $default = $data->default ?: 0;
            // [SQL安全改造] 默认值转义，防止单引号破坏SQL
            $default = str_replace(['\\', "'"], ['\\\\', "\\'"], (string)$default);
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL DEFAULT  \'' . $default . '\' ';
        } elseif ($data->datatype == 'float') {
            $fieldtype = $fieldtype ?: 'double';
            $length = $length ?: '15,4';
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL ';
        } elseif ($data->datatype == 'price') {
            $fieldtype = $fieldtype ?: 'decimal';
            $length = $length ?: '15,2';
            $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL ';
        } elseif ($data->datatype == 'hidden') {
            $fieldtype = $fieldtype ?: 'VARCHAR';
            if (in_array($fieldtype, ['text', 'mediumtext', 'longtext'])) {
                $sql .= $fieldtype . ' NOT NULL ';
            } else {
                $length = $length ?: '250';
                // [SQL安全改造] 默认值转义，防止单引号破坏SQL
                $default = $data->default ? ' DEFAULT  \'' . str_replace(['\\', "'"], ['\\\\', "\\'"], (string)$data->default) . '\' ' : '';
                $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL ' . $default;
            }
        } else {
            $fieldtype = $fieldtype ?: 'VARCHAR';
            if (in_array($fieldtype, ['text', 'mediumtext', 'longtext', 'year', 'date', 'datetime', 'timestamp', 'geometry',
                'polygon', 'point', 'linestring', 'multipoint', 'multilinestring', 'multipolygon', 'geometrycollection', 'set', 'enum'])) {
                $sql .= $fieldtype . ' NOT NULL ';
            } else {
                $length = $length ?: '250';
                $default = $data->default ?: (strpos($fieldtype, 'int') !== false ? '0' : '');
                // [SQL安全改造] 字段默认值转义，防止单引号破坏SQL
                $default = str_replace(['\\', "'"], ['\\\\', "\\'"], (string)$default);
                $sql .= $fieldtype . '( ' . $length . ' ) NOT NULL DEFAULT  \'' . $default . '\' ';
            }
        }

        $db = $this->t()->db();
        //生成字段注释
        $comment = $data->title;
        if (!empty($data->rules)) {
            $arr = [];
            foreach (unserialize($data->rules) as $k1 => $v1) {
                $arr[] = $k1 . '=' . $v1;
            }
            $comment .= '(' . implode(',', $arr) . ')';
        }
        $comment = mb_substr($comment, 0, 255, 'utf-8');
        // [SQL安全改造] 注释转义，防止单引号破坏SQL
        $comment = str_replace(['\\', "'"], ['\\\\', "\\'"], (string)$comment);
        $query = $db->query($sql . ' COMMENT \'' . $comment . '\'');

        //非多行文本才能创建索引
        if (!in_array($data->datatype, ['multitext', 'htmltext', 'imgs', 'serialize'])) {
            if (empty($fields[$data->identifier]['Key']) && $data->search == 1) {
                $query = $db->query('ALTER TABLE  `' . $tableName . '` ADD INDEX (`' . $data->identifier . '`)');
            }
            if (!empty($fields[$data->identifier]['Key']) && $data->search == 2) {
                $query = $db->query('ALTER TABLE  `' . $tableName . '` DROP INDEX `' . $data->identifier . '`');
            }
        }
        return $db->affectedRows($query);
    }

    /**
     * 删除字段
     * @param string $table
     * @param string $identifier
     * @return int
     */
    public function fieldDelete(string $table, string $identifier): int
    {
        // [SQL安全改造] 表名与字段名白名单校验，防止ALTER注入
        if (empty($table) || empty($identifier) || !preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            return 0;
        }
        $fields = $this->fetchAllField($table);
        if (!empty($fields[$identifier])) {
            $db = $this->t()->db();
            $query = $db->query('ALTER TABLE  `' . $this->setting['db']['tablepre'] . $table . '` DROP `' . $identifier . '`');
            return $db->affectedRows($query);
        }
        return 0;
    }
}
