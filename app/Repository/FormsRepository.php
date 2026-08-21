<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\FormsEntity;
use SlimCMS\Abstracts\RepositoryAbstract;
use SlimCMS\Error\TextException;

class FormsRepository extends RepositoryAbstract
{
    public function fetch(string $field, int $cacheTime = 0): ?FormsEntity
    {
        $data = parent::fetch($field, $cacheTime);
        return $data ? FormsEntity::fromArray($data) : null;
    }

    public function fetchList(string $field, string $indexField = '', int $cacheTime = 0): array
    {
        $data = parent::fetchList($field, $indexField, $cacheTime);
        return FormsEntity::fromArrayList($data);
    }

    public function list(string $fields = 'id,createtime', int $page = 1, int $pagesize = 30): array
    {
        $data = parent::list($fields, $page, $pagesize);
        $data['list'] = FormsEntity::fromArrayList($data['list']);
        return $data;
    }

    public function getTable(int $id): ?string
    {
        return $this->withWhere(['id' => $id])->fetch('table')?->table;
    }

    /**
     * 删除表
     * @param array $tables
     * @return bool
     */
    public function dropTable(array $tables): bool
    {
        if (empty($tables)) {
            return false;
        }
        $db = $this->t()->db();
        foreach ($tables as $v) {
            // [SQL安全改造] 表名白名单校验，防止DROP注入
            if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$v)) {
                continue;
            }
            $tableName = $this->setting['db']['tablepre'] . $v;
            $db->query('DROP TABLE IF EXISTS `' . $tableName . '`');
        }
        return true;
    }

    /**
     * 检查表是否存在
     * @param string $table
     * @return bool
     * @throws TextException
     */
    public function tableExist(string $table): bool
    {
        $db = $this->t()->db();
        // [SQL安全改造] SHOW TABLES LIKE 参数化，防止表名注入
        return $db->fetch('SHOW TABLES LIKE ?', [$this->setting['db']['tablepre'] . $table]) ? true : false;
    }

    /**
     * 创建表单数据表
     * @param string $table
     * @param string $name
     * @return bool
     */
    public function createTable(string $table, string $name = ''): bool
    {
        if (empty($table)) {
            return false;
        }
        // [SQL安全改造] 表名白名单校验，防止CREATE注入
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        if ($this->tableExist($table)) {
            return false;
        }
        // [SQL安全改造] 表注释（表单名）转义，防止单引号破坏SQL
        $name = str_replace(['\\', "'"], ['\\\\', "\\'"], (string)$name);
        $sql = "CREATE TABLE IF NOT EXISTS `" . $this->setting['db']['tablepre'] . $table . "`(
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`ischeck` tinyint(1) NOT NULL default '2' COMMENT '是否审核(1=已审核，2=未审核)',
				`createtime` bigint(11) NOT NULL default '0' COMMENT '创建时间',
				`ip` varchar(20) NOT NULL default '' COMMENT '创建IP',
				PRIMARY KEY  (`id`)\r\n) ENGINE=innoDB DEFAULT CHARSET=" . $this->setting['db']['dbcharset'] . " COMMENT='" . $name . "'; ";
        $db = $this->t()->db();
        $query = $db->query($sql);
        $db->affectedRows($query);
        return true;
    }

    /**
     * 表单列表
     * @return array
     * @throws TextException
     */
    public function tableList(): array
    {
        static $list = [];
        if (empty($list)) {
            $list = $this->withWhere(['ischeck' => 1])
                ->withOrderBy('weight')
                ->withLimit(1000)
                ->list('id,name,jumpurl,types,weight')['list'];
        }
        return $list;
    }
}
