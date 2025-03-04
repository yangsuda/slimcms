<?php
declare(strict_types=1);

namespace App\Core;


use SlimCMS\Helper\File;
use SlimCMS\Helper\Str;
use SlimCMS\Interfaces\OutputInterface;

class Output extends \SlimCMS\Core\Output
{
    /**
     * {@inheritDoc}
     */
    public function withCode(int $code, $param = []): OutputInterface
    {
        $res = parent::withCode($code, $param);
        if ($res->getCode() != 200) {
            $p = aval($_POST, 'p') ?: aval($_GET, 'p');
            $path = Str::htmlspecialchars($p);
            File::log('errorCode/' . date('Y') . '/' . date('m'))
                ->info('报错信息', ['code' => $res->getCode(), 'msg' => $res->getMsg(), 'path' => $path]);
        }
        return $res;
    }
}
