<?php

declare(strict_types=1);

namespace app\Repository;

use app\Model\entity\PluginsEntity;
use SlimCMS\Abstracts\RepositoryAbstract;

class PluginsRepository extends RepositoryAbstract
{
    protected ?string $entityClass = PluginsEntity::class;

    public function fetchByIdIdentifier(string $identifier): ?PluginsEntity
    {
        return $this->withWhere(['identifier' => $identifier])->fetch('id,isinstall,available');
    }

    /**
     * 卸载插件
     * @param int $id
     * @return bool
     * @throws \SlimCMS\Error\TextException
     */
    public function uninstall(int $id): bool
    {
        if (empty($id)) {
            return false;
        }
        $this->withWhere(['id' => $id])->batchUpdate(['isinstall' => -1, 'available' => -1]);
        return true;
    }

    /**
     * 开启/关闭插件
     * @param int $id
     * @param int $switch
     * @return bool
     * @throws \SlimCMS\Error\TextException
     */
    public function openSwitch(int $id, int $switch): bool
    {
        if (empty($id) || empty($switch)) {
            return false;
        }
        $this->withWhere(['id' => $id])->batchUpdate(['available' => $switch]);
        return true;
    }

    public function excuteSql(string $sql): bool
    {
        if (empty($sql)) {
            return false;
        }
        $this->t()->db()->query($sql);
        return true;
    }
}
