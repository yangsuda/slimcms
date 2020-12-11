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
        $param = array_map('strtolower', $param);
        $row = self::t('wxusers')->withWhere(['openid' => $param['openid']])->fetch();
        $data = [];
        !empty($param['nickname']) && $data['nickname'] = $param['nickname'];
        !empty($param['headimgurl']) && $data['headimgurl'] = $param['headimgurl'];
        !empty($param['avatarurl']) && empty($data['headimgurl']) && $data['headimgurl'] = $param['avatarurl'];
        if (!empty($row)) {
            $data && self::t('wxusers')->update($row['id'], $data);
        } else {
            $data['openid'] = $param['openid'];
            $data['createtime'] = TIMESTAMP;
            $data['ip'] = Ipdata::getip();
            self::t('wxusers')->insert($data);
        }
        return self::$output->withCode(200);
    }
}
