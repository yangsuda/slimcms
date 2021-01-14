<?php
/**
 * 阿里云短信发送类
 * 使用前先加载阿里云SDK:composer require alibabacloud/client
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\aliyun;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Interfaces\OutputInterface;

class AliSms extends ModelAbstract
{

    /**
     * RAM账号，创建地址：https://ram.console.aliyun.com/users
     * @var string
     */
    private static $accessKeyId = '';

    /**
     * RAM密码
     * @var string
     */
    private static $accessKeySecret = '';

    /**
     * 签名，申请地址：https://dysms.console.aliyun.com/dysms.htm?#/international/sign/add
     * @var string
     */
    private static $SignName = '';

    /**
     * 模版CODE，申请地址：https://dysms.console.aliyun.com/dysms.htm?#/international/template/add
     * @var string
     */
    private static $TemplateCode = '';

    /**
     * 指定请求的区域，不指定则使用客户端区域、默认区域
     * @var string
     */
    private static $RegionId = 'cn-hangzhou';

    /**
     * 发送短信
     * @param string $mobile 手机
     * @param array $param 模板中设置的参数
     * @return OutputInterface
     */
    public static function sendSms(string $mobile, array $param): OutputInterface
    {
        AlibabaCloud::accessKeyClient(self::$accessKeyId, self::$accessKeySecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => self::$RegionId,
                        'PhoneNumbers' => $mobile,
                        'SignName' => self::$SignName,
                        'TemplateCode' => self::$TemplateCode,
                        'TemplateParam' => json_encode($param),
                    ],
                ])
                ->request();
            $res = $result->toArray();
            if ($res['Code'] != 'OK') {
                return self::$output->withCode(21000, ['msg' => $res['Message']]);
            }
            return self::$output->withCode(200);
        } catch (ClientException $e) {
            return self::$output->withCode(21000, ['msg' => $e->getErrorMessage()]);
        } catch (ServerException $e) {
            return self::$output->withCode(21000, ['msg' => $e->getErrorMessage()]);
        }
    }
}
