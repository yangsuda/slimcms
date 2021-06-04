<?php
/**
 * 微信小程序模型类
 * @author zhucy
 */
declare(strict_types=1);

namespace App\Core;

use SlimCMS\Helper\Ipdata;

class Wxxcx extends \SlimCMS\Core\Wxxcx
{
    /**
     * 用户保存
     * @param $param
     * @return array
     */
    public static function userSave($param)
    {
        if (empty($param['openid'])) {
            return self::$output->withCode(21003);
        }
        $row = self::t('wxusers')->withWhere(['openid' => $param['openid']])->fetch();
        $data = [];
        !empty($param['nickName']) && $data['nickname'] = $param['nickName'];
        !empty($param['avatarUrl']) && $data['headimgurl'] = $param['avatarUrl'];
        if (!empty($row)) {
            $data && self::t('wxusers')->withWhere($row['id'])->update($data);
        } else {
            $data['openid'] = $param['openid'];
            $data['createtime'] = TIMESTAMP;
            $data['ip'] = Ipdata::getip();
            self::t('wxusers')->insert($data);
        }
        return self::$output->withCode(200);
    }
}
