<?php

declare(strict_types=1);

namespace app\Service\admin;

use app\Repository\ArchivedataRepository;
use app\Repository\FormsRepository;
use Slim\App;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Interfaces\RepositoryFactoryInterface;

class RecoveryService extends ServiceAbstract
{
    protected $archivedataRepository;
    protected $formsRepository;
    protected RepositoryFactoryInterface $repoFactory;

    public function __construct(App $app, ArchivedataRepository $archivedataRepository, FormsRepository $formsRepository, RepositoryFactoryInterface $repoFactory)
    {
        parent::__construct($app);
        $this->archivedataRepository = $archivedataRepository;
        $this->formsRepository = $formsRepository;
        $this->repoFactory = $repoFactory;
    }

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
        $data = $this->archivedataRepository->withWhere(['ids' => $id])->fetch('formid,content');
        if (empty($data) || !$data->has('content')) {
            return $this->output->withCode(21001);
        }
        $content = json_decode($data->content, true);
        $table = $this->formsRepository->withWhere(['id' => $data->formid])->fetch('table')?->table;
        if (empty($table)) {
            return $this->output->withCode(21001);
        }
        // forTable() 内部对未生成 Repository 类的表自动回退通用仓库
        $this->repoFactory->forTable($table)->insert($content);
        $this->archivedataRepository->delete($id);
        return $this->output->withCode(200, 211031);
    }
}
