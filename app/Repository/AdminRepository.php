<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\AdminEntity;
use Slim\App;
use SlimCMS\Abstracts\RepositoryAbstract;
use SlimCMS\Core\Form\FormQueryServiceInterface;
use SlimCMS\Core\Form\FormWriteServiceInterface;
use SlimCMS\Core\Redis;
use SlimCMS\Helper\Crypt;
use SlimCMS\Interfaces\UploadInterface;

class AdminRepository extends RepositoryAbstract
{
    private UploadInterface $uploader;

    public function __construct(App $app, FormWriteServiceInterface $formWrite, FormQueryServiceInterface $formQuery, Redis $redis, UploadInterface $uploader)
    {
        parent::__construct($app, $formWrite, $formQuery, $redis);
        $this->uploader = $uploader;
    }

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
        $data['list'] = AdminEntity::fromArrayList($data['list']);
        return $data;
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
            ->withRespExtraRowFields('_groupid')->fetch('id,groupid,userid,logintime,loginip,realname,headimgurl');
        if (!empty($admin)) {
            $admin->setRelation('adminAuth', Crypt::encrypt((string)$admin->id));
            $admin->setRelation('thumbnail', $admin?->headimgurl ?
                $this->uploader->copyImage($admin->headimgurl, 100, 100) :
                $this->config['resourceUrl'] . 'assets/images/avatars/admin.png');
        }
        return $admin;
    }
}
