<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\SysenumEntity;
use SlimCMS\Abstracts\RepositoryAbstract;

class SysenumRepository extends RepositoryAbstract
{
    public function fetch(string $field, int $cacheTime = 0): ?SysenumEntity
    {
        $data = parent::fetch($field, $cacheTime);
        return $data ? SysenumEntity::fromArray($data) : null;
    }

    public function fetchList(string $field, string $indexField = '', int $cacheTime = 0): array
    {
        $data = parent::fetchList($field, $indexField, $cacheTime);
        return SysenumEntity::fromArrayList($data);
    }

    public function list(string $fields = 'id,createtime', int $page = 1, int $pagesize = 30): array
    {
        $data = parent::list($fields, $page, $pagesize);
        $data['list'] = SysenumEntity::fromArrayList($data['list']);
        return $data;
    }

    /**
     * 按上下级获取相关数据
     * @param string $egoup
     * @param int $reid
     * @param string $fields
     * @return array
     * @throws \SlimCMS\Error\TextException
     */
    public function subordinate(string $egoup, int $reid = 0, string $fields = 'id,ename,reid,evalue'): array
    {
        if (empty($egoup) || empty($fields)) {
            return [];
        }
        $list = $this->withWhere(['egroup' => $egoup, 'reid' => $reid, 'evalueOverNil' => 1, 'ischeck' => 1])
            ->withOrderby('displayorder')
            ->fetchList($fields);
        foreach ($list as $v) {
            $v->setRelation('subList', $this->subordinate($egoup, $v->id, $fields));
        }
        return $list;
    }

    /**
     * 所有下级
     * @param string $egoup
     * @param int $reid
     * @param string $fields
     * @return array
     * @throws \SlimCMS\Error\TextException
     */
    public function allSubordinate(string $egoup, int $reid = 0, bool $self = false, string $fields = 'id,ename,reid,evalue'): array
    {
        if (empty($egoup) || empty($fields)) {
            return [];
        }
        $list = $this->withWhere(['egroup' => $egoup, 'reid' => $reid, 'evalue>0', 'ischeck' => 1])
            ->withOrderby('displayorder')
            ->fetchList($fields);
        $data = $list;
        foreach ($list as $v) {
            $subList = $this->allSubordinate($egoup, $v->id, false, $fields);
            $subList && $data = array_merge($data, $subList);
        }
        if ($self === true) {
            $data = array_merge($data, [$this->withWhere(['id' => $reid])->fetch($fields)]);
        }
        return $data;
    }

    /**
     * 递归上级
     * @param int $id
     * @return array
     */
    public function superior(string $egoup, int $id, int $apptend = 0): array
    {
        if (empty($egoup) || empty($id)) {
            return [];
        }
        $row = $this->withWhere(['egroup' => $egoup, 'id' => $id])->fetch('id,ename,reid');
        if (empty($row)) {
            return [];
        }
        $user = $apptend == 1 ? [$row] : [];
        if ($row->reid > 0) {
            $user[] = $this->superior($egoup, $row->reid, 1);
        }
        return $user;
    }
}
