<?php

declare(strict_types=1);

namespace app\Service\admin;

use app\Repository\ArchivedataRepository;
use app\Repository\FormsRepository;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Interfaces\OutputInterface;

class RecoveryService extends ServiceAbstract
{
    /**
     * 恢复数据
     * @param int $id
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function recovery(int $id): OutputInterface
    {
        if (empty($id)) {
            return $this->output->withCode(21002);
        }
        $data = $this->r(ArchivedataRepository::class)->withWhere(['ids' => $id])->fetch('formid,content');
        if (empty($data) || !$data->has('content')) {
            return $this->output->withCode(21001);
        }
        $content = unserialize($data->content);
        $table = $this->r(FormsRepository::class)->withWhere(['id' => $data->formid])->fetch('table')?->table;
        if (empty($table)) {
            return $this->output->withCode(21001);
        }
        $this->r($this->getRepositoryClassName($table))->insert($content);
        $this->r(ArchivedataRepository::class)->delete($id);
        return $this->output->withCode(200, 211031);
    }
}
