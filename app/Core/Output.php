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
            $post = Str::htmlspecialchars($_POST);
            $get = Str::htmlspecialchars($_GET);
            $p = aval($post, 'p') ?: aval($get, 'p');
            $appid = aval($post, 'appid') ?: aval($get, 'appid');
            File::log('errorCode/' . date('Y') . '/' . date('m'))
                ->info('报错信息', [
                    'code' => $res->getCode(),
                    'msg' => $res->getMsg(),
                    'path' => $p,
                    'file' => $file,
                    'appid' => $appid ? Crypt::decrypt($appid) : '',
                    'post' => $post,
                    'get' => $get,
                    'ip' => Ipdata::getip(),
                    'user_agent' => aval($_SERVER, 'HTTP_USER_AGENT'),
                ]);
        }
        return $res;
    }
}
