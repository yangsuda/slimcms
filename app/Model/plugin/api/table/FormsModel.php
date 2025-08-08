<?php
declare(strict_types=1);

namespace App\Model\plugin\api\table;

use App\Model\plugin\api\ApiModel;
use App\Repository\Forms;
use SlimCMS\Interfaces\OutputInterface;

class FormsModel extends ApiModel
{
    public static function add(array $param): OutputInterface
    {
        Forms::validCheck($param);
        return Forms::add($param);
    }

    public static function edit(int $id, array $param): OutputInterface
    {
        Forms::validCheck($param, $id);
        return Forms::edit($id, $param, ['callback' => __CLASS__]);
    }

    public static function list(array $param): OutputInterface
    {
        $param['callback'] = __CLASS__;
        return Forms::list($param);
    }

    public static function detail(int $id, string $fields, string $extraFields = '', array $param = []): OutputInterface
    {
        $param['callback'] = __CLASS__;
        return Forms::detail($id, $fields, $extraFields, $param);
    }

    public static function delete(int $id): OutputInterface
    {
        return Forms::delete($id);
    }

    public static function count(array $param = [], string $fields = '*', int $cacheTime = 0): int
    {
        $param['callback'] = __CLASS__;
        return Forms::count($param, $fields, $cacheTime);
    }

    public static function sum(string $fields, array $param = []): float
    {
        $param['callback'] = __CLASS__;
        return Forms::sum($fields, $param);
    }
}
