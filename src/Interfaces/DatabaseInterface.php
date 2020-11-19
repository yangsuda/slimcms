<?php
declare(strict_types=1);

namespace SlimCMS\Interfaces;


interface DatabaseInterface
{
    /**
     * 数据库连接
     */
    public function connect();

    /**
     * 返回数据库链接实例
     * @return Dbpdo
     */
    public function getLink();

    /**
     * SQL请求
     * @param $sql
     */
    public function query(string $sql);

    /**
     * 反馈某条数据结果
     * @param string $sql
     * @return array
     */
    public function fetch(string $sql);

    /**
     * 查询列表数据
     * @param string $sql
     * @param string $keyfield
     * @return array
     */
    public function fetchList(string $sql): array;

    /**
     * 返回查询某字段的值
     * @param string $sql
     * @param int $columnNumber
     * @return string
     */
    public function fetchColumn(string $sql, $columnNumber = 0);

    /**
     * 返回插入数据的自增ID
     * @return mixed
     */
    public function insertId();

    /**
     * 返回受上一个 SQL 语句影响的行数
     * @param $query
     * @return int
     */
    public function affectedRows($query): int;
}
