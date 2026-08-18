<?php
declare(strict_types=1);

namespace app\Model\entity;

use SlimCMS\Abstracts\EntityAbstract;

class Forms_fieldsEntity extends EntityAbstract
{
    protected array $casts = [
        'id' => 'int',
        'createtime' => 'int',
        'formid' => 'int',
        'datatype' => 'string',
        'available' => 'int',
        'identifier' => 'string',
        'infront' => 'int',
        'required' => 'int',
        'unique' => 'int',
        'search' => 'int',
        'orderby' => 'int',
        'inlistcp' => 'int',
        'forbidedit' => 'int',
        'fieldtype' => 'string',
    ];
}
