<?php
declare(strict_types=1);

namespace App\Model\plugin\api\table;

use App\Model\plugin\api\ApiModel;
use App\Repository\Forms_fields;
use SlimCMS\Interfaces\OutputInterface;

class Forms_fieldsModel extends ApiModel
{
    public static function add(array $param): OutputInterface
    {
        Forms_fields::validCheck($param);
        return Forms_fields::add($param);
    }

    public static function edit(int $id, array $param): OutputInterface
    {
        Forms_fields::validCheck($param, $id);
        return Forms_fields::edit($id, $param, ['callback' => __CLASS__]);
    }

    public static function list(array $param): OutputInterface
    {
        $param['callback'] = __CLASS__;
        return Forms_fields::list($param);
    }

    public static function detail(int $id, string $fields, string $extraFields = '', array $param = []): OutputInterface
    {
        $param['callback'] = __CLASS__;
        return Forms_fields::detail($id, $fields, $extraFields, $param);
    }

    public static function delete(int $id): OutputInterface
    {
        return Forms_fields::delete($id);
    }
}
