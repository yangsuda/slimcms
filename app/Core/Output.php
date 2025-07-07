<?php
declare(strict_types=1);

namespace App\Core;


use SlimCMS\Helper\Crypt;
use SlimCMS\Helper\File;
use SlimCMS\Helper\Ipdata;
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
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $file = !empty($backtrace[0]['file']) ? $backtrace[0]['file'] . ':' . $backtrace[0]['line'] : '';
            $p = aval($_POST, 'p') ?: aval($_GET, 'p');
            $appid = aval($_POST, 'appid') ?: aval($_GET, 'appid');
            File::log('errorCode/' . date('Y') . '/' . date('m'))
                ->info('报错信息', [
                    'code' => $res->getCode(),
                    'msg' => $res->getMsg(),
                    'path' => Str::htmlspecialchars($p),
                    'file' => $file,
                    'appid' => $appid ? Crypt::decrypt($appid) : '',
                    'post' => $_POST,
                    'get' => $_GET,
                    'ip' => Ipdata::getip(),
                    'user_agent' => aval($_SERVER, 'HTTP_USER_AGENT'),
                ]);
        }
        return $res;
    }
}
