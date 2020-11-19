<?php

namespace app\model\admincp;


use cs090\core\Table;
use cs090\core\Upload;
use cs090\helper\Http;
use cs090\helper\Ipdata;

class MainModel extends AdmincpModel
{
    /**
     * 恢复数据
     * @param $id
     * @return array
     */
    public static function recovery($id)
    {
        if (empty($id)) {
            return self::result(21002);
        }
        $row = Table::t('archivedata')->fetch($id);
        if (empty($row)) {
            return self::result(21001);
        }
        $content = unserialize($row['content']);
        $table = Table::t('forms')->fetch($row['formid'], 'table');
        Table::t($table)->insert($content);
        Table::t('archivedata')->delete($id);
        return self::result(200, '', 211031);
    }

    /**
     * 指定此三个文件夹文件做安全校验
     */
    public static function fileVerify()
    {
        $dirs = ['app', 'template', 'vendor'];
        foreach ($dirs as $dir) {
            self::getFiles(CSROOT . $dir);
        }
        //检测是否有删除
        $list = Table::t('fileverify')->fetchList();
        foreach ($list as $v) {
            if (!is_file(CSROOT . $v['filename'])) {
                Table::t('fileverify')->update($v['id'], ['status' => 4]);
            }
        }
        return self::result(200, '', 21017, '?p=forms/dataList&did=3');
    }

    /**
     * 遍历相关文件夹并将所有文件key入库
     * @param $directory
     */
    private static function getFiles($directory)
    {
        $exempt = ['.', '..', '.ds_store', '.svn'];
        $directory = preg_replace("/\/$/", "", $directory) . '/';
        $handle = opendir($directory);
        while (false !== ($resource = readdir($handle))) {
            if (!in_array(strtolower($resource), $exempt)) {
                //排除目录
                if (is_dir($directory . $resource . '/')) {
                    self::getFiles($directory . $resource . '/');
                } else {
                    $file = $directory . '/' . $resource;
                    $srcverifykey = md5_file($file);
                    $filename = str_replace(CSROOT, '', $file);
                    $row = Table::t('fileverify')->fetch(['filename' => $filename]);
                    if (empty($row)) {
                        Table::t('fileverify')->insert(['filename' => $filename, 'srcverifykey' => $srcverifykey, 'status' => 3, 'createtime' => TIMESTAMP, 'ip' => Ipdata::getip()]);
                    } else {
                        $status = $row['srcverifykey'] == $srcverifykey ? 1 : 2;
                        Table::t('fileverify')->update($row['id'], ['curverifykey' => $srcverifykey, 'status' => $status]);
                    }
                }
            }
        }
        closedir($handle);
    }

    /**
     * 更新文件校验KEY
     * @param $file
     * @return array
     */
    public static function updateVerifyKey($file)
    {
        if (empty($file)) {
            return self::result(21002);
        }
        $row = Table::t('fileverify')->fetch(['filename' => $file]);
        if (empty($row)) {
            return self::result(21001);
        }
        if (!is_file(CSROOT . $row['filename'])) {
            Table::t('fileverify')->delete($row['id']);
        } else {
            Table::t('fileverify')->update($row['id'], ['srcverifykey' => $row['curverifykey'], 'status' => 1]);
        }
        return self::result(200, '', 21017, '?p=forms/dataList&did=3');
    }

    /**
     * 删除某个附件
     * @param $param
     * @return array
     */
    public static function delImg($param)
    {
        if (empty($param['did']) || empty($param['id']) || empty($param['identifier'])) {
            return self::result(21002);
        }
        $tableName = Table::t('forms')->fetch($param['did'], 'table');
        $row = Table::t($tableName)->fetch($param['id']);
        if (empty($row[$param['identifier']])) {
            return self::result(21001);
        }
        Upload::uploadDel($row[$param['identifier']]);
        Table::t($tableName)->update($param['id'], [$param['identifier'] => '']);
        return self::result(200);
    }

    /**
     * 生成海报
     * @param $openid
     * @return array
     */
    public static function sharePic($openid)
    {
        if (empty($openid)) {
            return self::result(21002);
        }
        $user = Table::t('members')->fetch(['openid' => $openid]);
        if (empty($user)) {
            return self::result(21001);
        }
        $scene = $user['id'];
        $font = CSDATA . "fonts/nokia.ttf";
        $newpic = '/uploads/tmp/' . $scene . '.png';
        $dst = imagecreatefromstring(file_get_contents(CSPUBLIC . '/common/images/poster.jpg'));
        imagesavealpha($dst, true);

        //昵称
        //$black = imagecolorallocate($dst, 0, 0, 0);
        //$nickname = self::toEntities(self::filterEmoji($user['nickname']));
        //imagettftext($dst, 15, 0, 125, 745, $black, $font, $nickname);

        //头像
        $picurl = '/uploads/tmp/head' . md5($scene) . '.jpg';
        $data = Http::curlGet($user['headimgurl']);
        $data && file_put_contents(CSPUBLIC . $picurl, $data);

        //二维码
        $qrurl = self::$cfg['cfg']['basehost'] . self::$cfg['cfg']['entryFileName'].'?p=qrcode&scene=' . $scene . '&page=/pages/index/index';
        $qrcodeurl = '/uploads/tmp/qrcode' . $scene . '.jpg';
        file_put_contents(CSPUBLIC . $qrcodeurl, Http::curlGet($qrurl));

        //头像
        if ($data) {
            $srcpic = str_replace(self::$cfg['cfg']['basehost'], CSPUBLIC, copyImage($picurl, 110, 110));
            $src = imagecreatefromstring(file_get_contents($srcpic));
            Upload::uploadDel($picurl);
            imagecopy($dst, $src, 40, 950, 0, 0, 110, 110);
        }

        //头像边角处理
        $src = imagecreatefromstring(file_get_contents(CSPUBLIC . '/common/images/face_bg.png'));
        imagecopy($dst, $src, 40, 950, 0, 0, 110, 110);

        //二维码
        $srcpic = str_replace(self::$cfg['cfg']['basehost'], CSPUBLIC, copyImage($qrcodeurl, 142, 142));
        $src = imagecreatefromstring(file_get_contents($srcpic));
        Upload::uploadDel($picurl);
        imagecopy($dst, $src, 446, 935, 0, 0, 142, 142);


        imagepng($dst, CSPUBLIC . $newpic);
        imagedestroy($dst);
        return self::result(200, self::$cfg['cfg']['basehost'] . trim($newpic, '/'));
    }

    /**
     * 过滤emoji表情
     * @param $str
     * @return string|string[]|null
     */
    private static function filterEmoji($str)
    {
        $str = preg_replace_callback(    //执行一个正则表达式搜索并且使用一个回调进行替换
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

        return $str;
    }

    private static function toEntities($string)
    {
        $len = strlen($string);
        $buf = "";
        for ($i = 0; $i < $len; $i++) {
            if (ord($string[$i]) <= 127) {
                $buf .= $string[$i];
            } else if (ord($string[$i]) < 192) {
                //unexpected 2nd, 3rd or 4th byte
                $buf .= "&#xfffd";
            } else if (ord($string[$i]) < 224) {
                //first byte of 2-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 31) << 6) +
                    (ord($string[$i + 1]) & 63)
                );
                $i += 1;
            } else if (ord($string[$i]) < 240) {
                //first byte of 3-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 15) << 12) +
                    ((ord($string[$i + 1]) & 63) << 6) +
                    (ord($string[$i + 2]) & 63)
                );
                $i += 2;
            } else {
                //first byte of 4-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 7) << 18) +
                    ((ord($string[$i + 1]) & 63) << 12) +
                    ((ord($string[$i + 2]) & 63) << 6) +
                    (ord($string[$i + 3]) & 63)
                );
                $i += 3;
            }
        }
        return $buf;
    }
}
