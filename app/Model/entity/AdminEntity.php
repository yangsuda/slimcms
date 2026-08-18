<?php
/**
 * Admin Entity - 管理员实体类
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Model\entity;

use SlimCMS\Abstracts\EntityAbstract;

class AdminEntity extends EntityAbstract
{
    /**
     * 字段类型转换规则
     * 只需定义需要类型转换的字段，其他字段自动保持原始类型
     */
    protected array $casts = [
        'id' => 'int',
        'groupid' => 'int',
        'status' => 'int',
        'createtime' => 'int',
        'logintime' => 'int',
    ];

    /**
     * 输出时隐藏的敏感字段
     */
    protected array $hidden = ['pwd'];

    public function groupidEntity(): ?AdmingroupEntity
    {
        return $this?->_groupid;
    }
}
