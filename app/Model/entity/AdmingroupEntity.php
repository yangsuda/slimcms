<?php
/**
 * Admingroup Entity - 管理员组实体类
 *
 * @property int $id
 * @property string $groupname
 * @property string|null $purviews
 *
 * @author zhucy
 */
declare(strict_types=1);

namespace app\Model\entity;

use SlimCMS\Abstracts\EntityAbstract;

class AdmingroupEntity extends EntityAbstract
{
    /**
     * 获取权限列表
     *
     * @return array
     */
    public function getPurviewsList(): array
    {
        return $this->purviews ? explode(',', $this->purviews) : [];
    }

    /**
     * 判断是否有某个权限
     *
     * @param string $purview
     * @return bool
     */
    public function hasPurview(string $purview): bool
    {
        return in_array($purview, $this->getPurviewsList());
    }

    /**
     * 判断是否是超级管理员组
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return preg_match('/admin_AllowAll/i', $this->purviews ?? '') ? true : false;
    }
}
