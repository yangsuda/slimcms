<?php

declare(strict_types=1);

namespace app\Service\admin;

use app\Repository\Forms_fieldsRepository;
use app\Repository\FormsRepository;
use Slim\App;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Interfaces\OutputInterface;

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

    /**
     * 初始化表单
     * @param int $formId
     * @return bool
     */
    public function formInit(int $formId): bool
    {
        $table = $this->formsRepository->withWhere(['id' => $formId])->fetch('table')?->table;
        if (empty($table)) {
            return false;
        }
        $table = ucfirst($table);

        //生成Repository文件
        $path = CSAPP . 'Repository/';
        $file = $path . $table . 'Repository.php';
        if (is_writable($path) && !file_exists($file)) {
            $code = <<<EOT
<?php

declare(strict_types=1);

namespace app\Repository;

use app\\Model\\entity\\{$table}Entity;
use SlimCMS\\Abstracts\\RepositoryAbstract;

class {$table}Repository extends RepositoryAbstract
{
    public function fetch(string \$field, int \$cacheTime = 0): ?{$table}Entity
    {
        \$data = parent::fetch(\$field, \$cacheTime);
        return \$data ? {$table}Entity::fromArray(\$data) : null;
    }

    public function fetchList(string \$field, string \$indexField = '', int \$cacheTime = 0): array
    {
        \$data = parent::fetchList(\$field, \$indexField, \$cacheTime);
        return {$table}Entity::fromArrayList(\$data);
    }

    public function list(string \$fields = 'id,createtime', int \$page = 1, int \$pagesize = 30): array
    {
        \$data = parent::list(\$fields, \$page, \$pagesize);
        return {$table}Entity::fromArrayList(\$data);
    }
}
EOT;
            file_put_contents($file, $code);
        }

        //生成Entity文件
        $path = CSAPP . 'Model/entity/';
        $file = $path . $table . 'Entity.php';
        if (is_writable($path) && !file_exists($file)) {
            $code = <<<EOT
<?php
declare(strict_types=1);

namespace app\\Model\\entity;

use SlimCMS\\Abstracts\\EntityAbstract;

class {$table}Entity extends EntityAbstract
{

}
EOT;
            file_put_contents($file, $code);
        }

        //生成Req文件
        $path = CSAPP . 'Model/req/';
        $file = $path . $table . 'Req.php';
        if (is_writable($path) && !file_exists($file)) {
            $code = <<<EOT
<?php

declare(strict_types=1);

namespace app\\Model\\req;

use SlimCMS\\Abstracts\\ReqAbstract;

class {$table}Req extends ReqAbstract
{

}

EOT;
            file_put_contents($file, $code);
        }
        return true;
    }
}
