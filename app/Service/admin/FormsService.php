<?php

declare(strict_types=1);

namespace app\Service\admin;

use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use Slim\App;
use SlimCMS\Abstracts\ServiceAbstract;

class FormsService extends ServiceAbstract
{
    private FormsRepository $formsRepository;
    private Forms_fieldsRepository $forms_fieldsRepository;

    public function __construct(App $app, FormsRepository $formsRepository, Forms_fieldsRepository $forms_fieldsRepository)
    {
        parent::__construct($app);
        $this->formsRepository = $formsRepository;
        $this->forms_fieldsRepository = $forms_fieldsRepository;
    }

    /**
     * 获取表单字段映射
     * @param string $table
     * @return array
     */
    public function getFieldMap(string $table): array
    {
        if (empty($table)) {
            return [];
        }
        $formId = $this->formsRepository->withWhere(['table' => $table])->fetch('id')?->id;
        if (empty($formId)) {
            return [];
        }
        return $this->forms_fieldsRepository->withWhere(['formid' => $formId])->map('title', 'identifier');
    }
}
