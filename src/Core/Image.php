<?php
/**
 * 图片处理类
 * @author zhucy
 */

namespace SlimCMS\Core;

use SlimCMS\Abstracts\ModelAbstract;

class Image extends ModelAbstract
{
    private static $attachinfo;
    private static $targetfile;    //图片路径
    private static $imagecreatefromfunc;
    private static $imagefunc;
    private static $attach;
    private static $animatedgif;
    private static $watermarkquality;
    private static $watermarktext;
    private static $thumbstatus;
    private static $cfg = [];
    private static $watermarktype = '';
    private static $watermarktrans = '';

    private static function init()
    {
        //检测用户系统支持的图片格式
        self::$cfg['photo_type']['gif'] = FALSE;
        self::$cfg['photo_type']['jpeg'] = FALSE;
        self::$cfg['photo_type']['png'] = FALSE;
        self::$cfg['photo_type']['wbmp'] = FALSE;
        self::$cfg['photo_typenames'] = [];
        self::$cfg['photo_support'] = '';
        if (function_exists("imagecreatefromgif") && function_exists("imagegif")) {
            self::$cfg['photo_type']["gif"] = TRUE;
            self::$cfg['photo_typenames'][] = "image/gif";
            self::$cfg['photo_support'] .= "GIF ";
        }
        if (function_exists("imagecreatefromjpeg") && function_exists("imagejpeg")) {
            self::$cfg['photo_type']["jpeg"] = TRUE;
            self::$cfg['photo_typenames'][] = "image/pjpeg";
            self::$cfg['photo_typenames'][] = "image/jpeg";
            self::$cfg['photo_support'] .= "JPEG ";
        }
        if (function_exists("imagecreatefrompng") && function_exists("imagepng")) {
            self::$cfg['photo_type']["png"] = TRUE;
            self::$cfg['photo_typenames'][] = "image/png";
            self::$cfg['photo_typenames'][] = "image/xpng";
            self::$cfg['photo_support'] .= "PNG ";
        }
        if (function_exists("imagecreatefromwbmp") && function_exists("imagewbmp")) {
            self::$cfg['photo_type']["wbmp"] = TRUE;
            self::$cfg['photo_typenames'][] = "image/wbmp";
            self::$cfg['photo_support'] .= "WBMP ";
        }
    }

    /**
     * 复制指定大小图片
     * @param $pic
     * @param int $width
     * @param int $height
     * @return mixed|string
     */
    public static function copyImage($pic, $width = 1000, $height = 1000, $nopic = 'resources/global/images/nopic/nopic.jpg')
    {
        $attachmentHost = !empty(self::$config['attachmentHost']) ? self::$config['attachmentHost'] : self::$config['basehost'];
        $attachmentHost = rtrim($attachmentHost, '/') . '/';
        if (preg_match('/' . self::$config['domain'] . '/i', $pic)) {
            $pic = str_replace(rtrim(self::$config['basehost'], '/'), '', $pic);
        }
        if (preg_match("/^(https?:\/\/)/i", $pic)) {
            return $pic;
        }

        $pic = ltrim($pic, '/');
        $oldurl = CSPUBLIC . $pic;
        $ptype = strrchr($pic, '.');
        //如果有已经生成的图片直接返回
        $newpic = str_replace($ptype, "_{$width}x{$height}" . $ptype, $pic);
        if (is_file(CSPUBLIC . $newpic)) {
            return $attachmentHost . $newpic;
        }
        $imgdata = is_file($oldurl) ? @getimagesize($oldurl) : [];
        if (!$imgdata) {
            $pic = $nopic;
            $oldurl = CSPUBLIC . $pic;
            $ptype = strrchr($pic, '.');
            $imgdata = @getimagesize($oldurl);
        }
        if ($imgdata[0] > $width || $imgdata[1] > $height) {
            $newpic = str_replace($ptype, "_{$width}x{$height}" . $ptype, $pic);
            $newurl = CSPUBLIC . $newpic;
            if (is_file($newurl)) {
                return $attachmentHost . $newpic;
            }
            if (@copy($oldurl, $newurl)) {
                is_file($newurl) && self::resize($newurl, $width, $height) && Upload::save('/' . $newpic);
            }
            return $attachmentHost . $newpic;
        }
        return $attachmentHost . $pic;
    }

    private static function watermark_gd($preview = 0)
    {
        $photo = include CSDATA . 'mark/config.php';
        if (function_exists('imagecopy') && function_exists('imagealphablending') && function_exists('imagecopymerge')) {
            $imagecreatefunc = self::$imagecreatefromfunc;
            $imagefunc = self::$imagefunc;
            list($imagewidth, $imageheight) = self::$attachinfo;
            if (self::$watermarktype < 2) {
                $watermark_file = self::$watermarktype == 1 ? CSDATA . 'mark/mark.png' : CSDATA . 'mark/mark.gif';
                $watermarkinfo = @getimagesize($watermark_file);
                $watermark_logo = self::$watermarktype == 1 ? @imagecreatefrompng($watermark_file) : @imagecreatefromgif($watermark_file);
                if (!$watermark_logo) {
                    return;
                }
                list($logowidth, $logoheight) = $watermarkinfo;
            } else {
                $box = @imagettfbbox(self::$watermarktext['size'], self::$watermarktext['angle'], self::$watermarktext['fontpath'], self::$watermarktext['text']);
                $logowidth = max($box[2], $box[4]) - min($box[0], $box[6]);
                $logoheight = max($box[1], $box[3]) - min($box[5], $box[7]);
                $ax = min($box[0], $box[6]) * -1;
                $ay = min($box[5], $box[7]) * -1;
            }
            $wmwidth = $imagewidth - $logowidth;
            $wmheight = $imageheight - $logoheight;
            if ((self::$watermarktype < 2 && is_readable($watermark_file) || self::$watermarktype == 2) && $wmwidth > 10 && $wmheight > 10 && !self::$animatedgif) {
                switch ($photo['waterpos']) {
                    case 1:

                        $x = +5;
                        $y = +5;
                        break;
                    case 2:
                        $x = ($imagewidth - $logowidth) / 2;
                        $y = +5;
                        break;
                    case 3:
                        $x = $imagewidth - $logowidth - 5;
                        $y = +5;
                        break;
                    case 4:
                        $x = +5;
                        $y = ($imageheight - $logoheight) / 2;
                        break;
                    case 5:
                        $x = ($imagewidth - $logowidth) / 2;
                        $y = ($imageheight - $logoheight) / 2;
                        break;
                    case 6:
                        $x = $imagewidth - $logowidth - 5;
                        $y = ($imageheight - $logoheight) / 2;
                        break;
                    case 7:
                        $x = +5;
                        $y = $imageheight - $logoheight - 5;
                        break;
                    case 8:
                        $x = ($imagewidth - $logowidth) / 2;
                        $y = $imageheight - $logoheight - 5;
                        break;
                    case 9:
                        $x = $imagewidth - $logowidth - 5;
                        $y = $imageheight - $logoheight - 5;
                        break;
                }
                $dst_photo = @imagecreatetruecolor($imagewidth, $imageheight);
                if (self::$attachinfo[2] == 3) {
                    imagealphablending($dst_photo, false);//意思是不合并颜色,直接用图像颜色替换,包括透明色;
                    imagesavealpha($dst_photo, true);//意思是不要丢了图像的透明色;
                }
                $target_photo = $imagecreatefunc(self::$targetfile);
                self::$attachinfo[2] == 3 && imagesavealpha($target_photo, true);//意思是不要丢了图像的透明色;
                imagecopy($dst_photo, $target_photo, 0, 0, 0, 0, $imagewidth, $imageheight);
                if (self::$watermarktype == 1) {
                    imagecopy($dst_photo, $watermark_logo, $x, $y, 0, 0, $logowidth, $logoheight);
                } elseif (self::$watermarktype == 2) {
                    if ((self::$watermarktext['shadowx'] || self::$watermarktext['shadowy']) && self::$watermarktext['shadowcolor']) {
                        $shadowcolorrgb = explode(',', self::$watermarktext['shadowcolor']);
                        $shadowcolor = imagecolorallocate($dst_photo, $shadowcolorrgb[0], $shadowcolorrgb[1], $shadowcolorrgb[2]);
                        imagettftext($dst_photo, self::$watermarktext['size'], self::$watermarktext['angle'],
                            $x + $ax + self::$watermarktext['shadowx'], $y + $ay + self::$watermarktext['shadowy'], $shadowcolor,
                            self::$watermarktext['fontpath'], self::$watermarktext['text']);
                    }
                    $colorrgb = explode(',', self::$watermarktext['color']);
                    $color = imagecolorallocate($dst_photo, $colorrgb[0], $colorrgb[1], $colorrgb[2]);
                    imagettftext($dst_photo, self::$watermarktext['size'], self::$watermarktext['angle'],
                        $x + $ax, $y + $ay, $color, self::$watermarktext['fontpath'], self::$watermarktext['text']);
                } else {
                    imagealphablending($watermark_logo, true);
                    imagecopymerge($dst_photo, $watermark_logo, $x, $y, 0, 0, $logowidth, $logoheight, self::$watermarktrans);
                }
                $targetfile = !$preview ? self::$targetfile : './watermark_tmp.jpg';
                if (self::$attachinfo['mime'] == 'image/jpeg') {
                    $imagefunc($dst_photo, $targetfile, self::$watermarkquality);
                } else {
                    $imagefunc($dst_photo, $targetfile);
                }
                self::$attach['size'] = filesize(self::$targetfile);
            }
        }
    }

    /**
     * 图片处理成指定大小
     * @param $file
     * @param $width
     * @param $height
     */
    public static function imageResize($file, $width = '', $height = '')
    {
        $width = $width ?: self::$config['imgWidth'];
        $height = $height ?: self::$config['imgHeight'];
        if (self::$config['imgFull'] == '1') {
            self::resizeNew($file, $width, $height);
        } else {
            self::resize($file, $width, $height);
        }
    }

    /**
     *  缩图片自动生成函数，来源支持bmp、gif、jpg、png
     *  但生成的小图只用jpg或png格式
     * @param string $srcFile 图片路径
     * @param string $toW 转换到的宽度
     * @param string $toH 转换到的高度
     * @return    string
     */
    private static function resize($srcFile, $toW, $toH)
    {
        self::init();
        $toFile = $srcFile;
        $info = '';
        $srcInfo = getimagesize($srcFile, $info);
        switch ($srcInfo[2]) {
            case 1:
                if (!self::$cfg['photo_type']['gif']) return FALSE;
                $im = imagecreatefromgif($srcFile);
                break;
            case 2:
                if (!self::$cfg['photo_type']['jpeg']) return FALSE;
                $im = imagecreatefromjpeg($srcFile);
                break;
            case 3:
                if (!self::$cfg['photo_type']['png']) return FALSE;
                $im = imagecreatefrompng($srcFile);
                imagesavealpha($im, true);//意思是不要丢了图像的透明色;
                break;
            case 6:
                if (!self::$cfg['photo_type']['bmp']) return FALSE;
                $im = imagecreatefromwbmp($srcFile);
                break;
        }
        $srcW = imagesx($im);
        $srcH = imagesy($im);
        if ($srcW <= $toW && $srcH <= $toH) return TRUE;
        $toWH = $toW / $toH;
        $srcWH = $srcW / $srcH;
        if ($toWH <= $srcWH) {
            $ftoW = $toW;
            $ftoH = $ftoW * ($srcH / $srcW);
        } else {
            $ftoH = $toH;
            $ftoW = $ftoH * ($srcW / $srcH);
        }
        if ($srcW > $toW || $srcH > $toH) {
            if (function_exists("imagecreatetruecolor")) {
                @$ni = imagecreatetruecolor($ftoW, $ftoH);
                if ($ni) {
                    if ($srcInfo[2] == 3) {
                        imagealphablending($ni, false);//意思是不合并颜色,直接用图像颜色替换,包括透明色;
                        imagesavealpha($ni, true);//意思是不要丢了图像的透明色;
                    }
                    imagecopyresampled($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
                } else {
                    $ni = imagecreate($ftoW, $ftoH);
                    imagecopyresized($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
                }
            } else {
                $ni = imagecreate($ftoW, $ftoH);
                imagecopyresized($ni, $im, 0, 0, 0, 0, $ftoW, $ftoH, $srcW, $srcH);
            }

            switch ($srcInfo[2]) {
                case 1:
                    imagegif($ni, $toFile);
                    break;
                case 2:
                    $jpgQuality = aval(self::$config, 'jpgQuality', 95);
                    imagejpeg($ni, $toFile, $jpgQuality);
                    break;
                case 3:
                    imagepng($ni, $toFile);
                    break;
                case 6:
                    imagebmp($ni, $toFile);
                    break;
                default:
                    return FALSE;
            }
            $ni && imagedestroy($ni);
        }
        imagedestroy($im);
        return TRUE;
    }

    /**
     *  图片自动加水印函数
     * @access    public
     * @param string $srcFile 图片源文件
     * @return    string
     */
    public static function waterImg($srcFile)
    {
        $photo = include CSDATA . 'mark/config.php';
        $info = '';
        $srcInfo = @getimagesize($srcFile, $info);
        $srcFile_w = $srcInfo[0];
        $srcFile_h = $srcInfo[1];

        if ($srcFile_w < $photo['wwidth'] || $srcFile_h < $photo['wheight']) {
            return;
        }
        if ($photo['waterpos'] == 0) {
            $photo['waterpos'] = rand(1, 9);
        }
        $cfg_watermarktext = array();
        if ($photo['marktype'] == '2') {
            $cfg_watermarktext['fontpath'] = CSDATA . 'fonts/nokia.ttf';
        }
        $cfg_watermarktext['text'] = $photo['watertext'];
        $cfg_watermarktext['size'] = $photo['fontsize'];
        $cfg_watermarktext['angle'] = '0';
        $cfg_watermarktext['color'] = '255,255,255';
        $cfg_watermarktext['shadowx'] = '0';
        $cfg_watermarktext['shadowy'] = '0';
        $cfg_watermarktext['shadowcolor'] = '0,0,0';
        $photo['marktrans'] = 85;

        self::$thumbstatus = 0;
        self::$watermarktext = $cfg_watermarktext;
        self::$watermarkquality = $photo['marktrans'];
        self::$watermarktype = $photo['marktype'];
        self::$watermarktrans = $photo['diaphaneity'];
        self::$animatedgif = 0;
        self::$targetfile = $srcFile;
        self::$attachinfo = @getimagesize($srcFile);

        switch (self::$attachinfo['mime']) {
            case 'image/jpeg':
                self::$imagecreatefromfunc = function_exists('imagecreatefromjpeg') ? 'imagecreatefromjpeg' : '';
                self::$imagefunc = function_exists('imagejpeg') ? 'imagejpeg' : '';
                break;
            case 'image/gif':
                self::$imagecreatefromfunc = function_exists('imagecreatefromgif') ? 'imagecreatefromgif' : '';
                self::$imagefunc = function_exists('imagegif') ? 'imagegif' : '';
                break;
            case 'image/png':
                self::$imagecreatefromfunc = function_exists('imagecreatefrompng') ? 'imagecreatefrompng' : '';
                self::$imagefunc = function_exists('imagepng') ? 'imagepng' : '';
                break;
        }//为空则匹配类型的函数不存在

        self::$attach['size'] = empty(self::$attach['size']) ? @filesize($srcFile) : self::$attach['size'];
        if (self::$attachinfo['mime'] == 'image/gif') {
            $fp = fopen($srcFile, 'rb');
            $targetfilecontent = fread($fp, self::$attach['size']);
            fclose($fp);
            self::$animatedgif = strpos($targetfilecontent, 'NETSCAPE2.0') === false ? 0 : 1;
        }
        if (self::$attachinfo[0] <= $photo['wwidth'] && self::$attachinfo[1] <= $photo['wheight']) {
            return;
        }

        self::watermark_gd(0);
    }


    /**
     *  会对空白地方填充满
     * @param string $srcFile 图片路径
     * @param string $toW 转换到的宽度
     * @param string $toH 转换到的高度
     * @param string $toFile 输出文件到
     * @param string $issave 是否保存
     * @return    bool
     */

    private static function resizeNew($srcFile, $toW, $toH)
    {
        self::init();
        $toFile = $srcFile;
        $info = '';
        $srcInfo = getimagesize($srcFile, $info);
        switch ($srcInfo[2]) {
            case 1:
                if (!self::$cfg['photo_type']['gif']) return FALSE;
                $img = imagecreatefromgif($srcFile);
                break;
            case 2:
                if (!self::$cfg['photo_type']['jpeg']) return FALSE;
                $img = imagecreatefromjpeg($srcFile);
                break;
            case 3:
                if (!self::$cfg['photo_type']['png']) return FALSE;
                $img = imagecreatefrompng($srcFile);
                break;
            case 6:
                if (!self::$cfg['photo_type']['bmp']) return FALSE;
                $img = imagecreatefromwbmp($srcFile);
                break;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        if (!$width || !$height) {
            return FALSE;
        }

        $target_width = $toW;
        $target_height = $toH;
        $target_ratio = $target_width / $target_height;

        $img_ratio = $width / $height;

        if ($target_ratio > $img_ratio) {
            $new_height = $target_height;
            $new_width = $img_ratio * $target_height;
        } else {
            $new_height = $target_width / $img_ratio;
            $new_width = $target_width;
        }

        if ($new_height > $target_height) {
            $new_height = $target_height;
        }
        if ($new_width > $target_width) {
            $new_height = $target_width;
        }

        $new_img = ImageCreateTrueColor($target_width, $target_height);

        $bgcolor = self::$config['imgBgcolor'] == 0 ? ImageColorAllocate($new_img, 0xff, 0xff, 0xff) : 0;

        if (!@imagefilledrectangle($new_img, 0, 0, $target_width - 1, $target_height - 1, $bgcolor)) {
            return FALSE;
        }

        if (!@imagecopyresampled($new_img, $img, ($target_width - $new_width) / 2, ($target_height - $new_height) / 2, 0, 0, $new_width, $new_height, $width, $height)) {
            return FALSE;
        }

        switch ($srcInfo[2]) {
            case 1:
                imagegif($new_img, $toFile);
                break;
            case 2:
                imagejpeg($new_img, $toFile, 100);
                break;
            case 3:
                imagepng($new_img, $toFile);
                break;
            case 6:
                imagebmp($new_img, $toFile);
                break;
            default:
                return FALSE;
        }
        imagedestroy($new_img);
        imagedestroy($img);
        return TRUE;
    }
}
