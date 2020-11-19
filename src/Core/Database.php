<?php

/**
 * 数据库操作类
 * @author zhucy
 */
declare(strict_types=1);

namespace SlimCMS\Core;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Container\ContainerInterface;
use SlimCMS\Error\TextException;
use SlimCMS\Interfaces\DatabaseInterface;

class Database implements DatabaseInterface
{
    private $setting;
    public static $link;

    public function __construct(ContainerInterface $container)
    {
        $this->setting = $container->get('settings');
        self::$link = $this->connect();
        $error = self::$link->errorInfo();
        if (in_array($error[1], [2006, 2013])) {
            self::$link = $this->connect();
        }
        self::$link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        if (empty(self::$link)) {
            try {
                $db = &$this->setting['db'];
                $options = aval($db, 'pconnect') ? [\PDO::ATTR_PERSISTENT => true] : [];
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET character_set_connection=' . aval($db, 'dbcharset') .
                    ', character_set_results=' . aval($db, 'dbcharset') . ', character_set_client=binary, sql_mode=\'\'';
                $connecttype = aval($db, 'connecttype') == ':' ? ':' : ';port=';
                $dsn = 'mysql:host=' . aval($db, 'dbhost') . $connecttype . aval($db, 'dbport') . ';dbname=' . aval($db, 'dbname');
                return new PDO($dsn, aval($db, 'dbuser'), aval($db, 'dbpw'), $options);
            } catch (PDOException $e) {
                throw new TextException(21054, $e->getMessage(), 'pdo');
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getLink(): PDO
    {
        return self::$link;
    }

    /**
     * {@inheritDoc}
     */
    public function insertId()
    {
        return self::$link->lastInsertId();
    }

    /**
     * {@inheritDoc}
     */
    public function query($sql): PDOStatement
    {
        $this->checkQuery($sql);
        $query = self::$link->query($sql);
        if (!$query) {
            $error = self::$link->errorInfo();
            $msg = $error[0] . " " . $error[2] . " " . $error[1] . " " . $sql;
            throw new TextException(21055, $msg, 'pdo');
        }
        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(string $sql)
    {
        $query = $this->query($sql);
        $data = $query->fetch(PDO::FETCH_ASSOC);
        $query->closeCursor();
        return $data ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function fetchList(string $sql, string $keyfield = ''): array
    {
        $data = [];
        $query = $this->query($sql);
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            if ($keyfield && isset($row[$keyfield])) {
                $data[$row[$keyfield]] = $row;
            } else {
                $data[] = $row;
            }
        }
        $query->closeCursor();
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchColumn(string $sql, $columnNumber = 0)
    {
        $query = $this->query($sql);
        $data = $query->fetchColumn($columnNumber);
        $query->closeCursor();
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function affectedRows($query): int
    {
        $data = $query->rowCount();
        $query->closeCursor();
        return $data;
    }

    /**
     * SQL安全检测
     * @param string $sql
     * @throws TextException
     */
    private function checkQuery(string $sql)
    {
        $querysafe = $this->setting['security']['querysafe'];
        if ($querysafe['status']) {
            $sql = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
            if (strpos($sql, '/') === false && strpos($sql, '#') === false && strpos($sql, '-- ') === false) {
                $clean = preg_replace("/'(.+?)'/s", '', $sql);
            } else {
                $len = strlen($sql);
                $mark = $clean = '';
                for ($i = 0; $i < $len; $i++) {
                    $str = $sql[$i];
                    switch ($str) {
                        case '\'':
                            if (!$mark) {
                                $mark = '\'';
                                $clean .= $str;
                            } elseif ($mark == '\'') {
                                $mark = '';
                            }
                            break;
                        case '/':
                            if (empty($mark) && $sql[$i + 1] == '*') {
                                $mark = '/*';
                                $clean .= $mark;
                                $i++;
                            } elseif ($mark == '/*' && $sql[$i - 1] == '*') {
                                $mark = '';
                                $clean .= '*';
                            }
                            break;
                        case '#':
                            if (empty($mark)) {
                                $mark = $str;
                                $clean .= $str;
                            }
                            break;
                        case "\n":
                            if ($mark == '#' || $mark == '--') {
                                $mark = '';
                            }
                            break;
                        case '-':
                            if (empty($mark) && substr($sql, $i, 3) == '-- ') {
                                $mark = '-- ';
                                $clean .= $mark;
                            }
                            break;

                        default:

                            break;
                    }
                    $clean .= $mark ? '' : $str;
                }
            }

            $clean = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", "", strtolower($clean));

            if ($querysafe['afullnote']) {
                $clean = str_replace('/**/', '', $clean);
            }

            if (is_array($querysafe['dfunction'])) {
                foreach ($querysafe['dfunction'] as $fun) {
                    if (strpos($clean, $fun . '(') !== false) {
                        throw new TextException(21056, '', 'pdo');
                    }
                }
            }

            if (is_array($querysafe['daction'])) {
                foreach ($querysafe['daction'] as $action) {
                    if (strpos($clean, $action) !== false) {
                        throw new TextException(21056, '', 'pdo');
                    }
                }
            }

            if ($querysafe['dlikehex'] && strpos($clean, 'like0x')) {
                throw new TextException(21056, '', 'pdo');
            }

            if (is_array($querysafe['dnote'])) {
                foreach ($querysafe['dnote'] as $note) {
                    if (strpos($clean, $note) !== false) {
                        throw new TextException(21056, '', 'pdo');
                    }
                }
            }
        }
    }
}
