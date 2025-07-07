<?php
/**
 * 外部请求处理类
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Core;

use Respect\Validation\Exceptions\ValidationException;
use SlimCMS\Error\TextException;

class Request extends \SlimCMS\Core\Request
{
    protected function inputData(array $param): array
    {
        $data = [];
        foreach ($param as $k => $v) {
            $val = $this->getInput($k);
            if ($v instanceof \Respect\Validation\Validatable) {
                try {
                    $v->assert($val);
                } catch (ValidationException $e) {
                    !empty($e->getParams()['template']) && $e->updateTemplate($e->getParams()['template']);
                    throw new TextException(21000, ['msg' => $e->getMessage()]);
                }
                isset($val) && $data[$k] = $val;
                unset($param[$k]);
            }
        }
        $data = array_merge($data, parent::inputData($param));
        return $data;
    }
}
