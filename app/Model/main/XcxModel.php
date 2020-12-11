<?php

/**
 * 微信小程序模型类
 * @author zhucy
 * @date 2019.09.23
 */

namespace app\model\main;

use cs090\core\Table;
use cs090\core\Wxgzh;
use cs090\core\Wxxcx;
use cs090\helper\Ipdata;

class XcxModel extends Wxxcx
{
    public static $appid = 'wxbde661af0439c5e4';
    public static $appsecret = 'e2408b1fd25a8a0e8186412dab1ac988';

    /**
     * 验证请求来路
     * @return array
     */
    public static function verifyHttpReferer()
    {
        if (Ipdata::getip() == '222.92.73.130') {
            return self::result(200);
        }
        return parent::verifyHttpReferer();
    }

    /**
     * 用户保存
     * @param $param
     * @return array
     */
    public static function userSave($param)
    {
        if (empty($param['openid'])) {
            return self::result(21002);
        }
        $row = Table::t('wxusers')->fetch(['openid' => $param['openid']]);
        $data = [];
        !empty($param['nickname']) && $data['nickname'] = $param['nickname'];
        !empty($param['headimgurl']) && $data['headimgurl'] = $param['headimgurl'];
        !empty($param['avatarurl']) && empty($data['headimgurl']) && $data['headimgurl'] = $param['avatarurl'];
        if (!empty($row)) {
            $data && Table::t('wxusers')->update($row['id'], $data);
        } else {
            $data['openid'] = $param['openid'];
            $data['createtime'] = TIMESTAMP;
            $data['ip'] = Ipdata::getip();
            Table::t('wxusers')->insert($data);
        }
        return self::result(200);
    }
}
