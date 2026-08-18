<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\AdminEntity;
use SlimCMS\Abstracts\RepositoryAbstract;
use SlimCMS\Helper\Crypt;

class AdminRepository extends RepositoryAbstract
{
    public function fetch(string $field, int $cacheTime = 0): ?AdminEntity
    {
        $data = parent::fetch($field, $cacheTime);
        return $data ? AdminEntity::fromArray($data) : null;
    }

    public function fetchList(string $field, string $indexField = '', int $cacheTime = 0): array
    {
        $data = parent::fetchList($field, $indexField, $cacheTime);
        return AdminEntity::fromArrayList($data);
    }

    public function list(string $fields = 'id,createtime', int $page = 1, int $pagesize = 30): array
    {
        $data = parent::list($fields, $page, $pagesize);
        return AdminEntity::fromArrayList($data);
    }

    /**
     * 获取管理员信息
     * @param int $adminid
     * @return AdminEntity|null
     * @throws \SlimCMS\Error\TextException
     */
    public function adminInfo(int $adminid): ?AdminEntity
    {
        if (empty($adminid)) {
            return null;
        }
        $admin = $this->withWhere(['ids' => $adminid, 'status' => 1])
            ->withRespExtraRowFields('_groupid')->fetch('id,groupid,userid,logintime,loginip,realname');
        if (!empty($admin)) {
            $admin->setRelation('adminAuth', Crypt::encrypt((string)$admin->id));
        }
        return $admin;
    }

    /**
     * 保存管理员日志
     * @param array $data
     * @return bool
     * @throws \SlimCMS\Error\TextException
     */
    public function adminLogSave(array $data): bool
    {
        if (!empty($this->config['adminLog'])) {
            $this->r(AdminlogRepository::class)->add($data);
            return true;
        }
        return false;
    }
}
