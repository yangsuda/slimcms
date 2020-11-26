<?php
/**
 * 附件上传类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use Slim\Psr7\UploadedFile;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Abstracts\ModelAbstract;
use SlimCMS\Helper\File;
use SlimCMS\Interfaces\OutputInterface;

class Upload extends ModelAbstract
{
    /**
     * 图片H5上传数据处理
     * @param string $img
     * @return OutputInterface
     */
    public static function h5(string $img): OutputInterface
    {
        $img = preg_replace('/data:image\/(jpeg|png);base64,/i', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        if (empty($data)) {
            return self::$output->withCode(23001);
        }

        $dirname = !empty(self::$setting['attachment']['dirname']) ? trim(self::$setting['attachment']['dirname'], '/') : 'uploads';
        $file = uniqid() . '.jpg';
        $tmpPath = CSPUBLIC . $dirname . '/tmp/';
        File::mkdir($tmpPath);
        $fileUrl = $tmpPath . $file;
        $success = file_put_contents($fileUrl, $data);
        if (!$success) {
            return self::$output->withCode(23014);
        }
        $post = [];
        $post['files']['tmp_name'] = $fileUrl;
        $post['files']['name'] = $file;
        $post['files']['type'] = 'image/jpeg';
        return self::$output->withCode(200)->withData($post);
    }

    /**
     * 上传附件
     * @param array $post
     * @return OutputInterface
     */
    public static function upload($post): OutputInterface
    {
        if (is_string($post)) {
            $result = self::h5($post);
            if ($result->getCode() != 200) {
                return $result;
            }
            $post = $result->getData();
        }
        if (empty($post['files']['tmp_name'])) {
            return self::$output->withCode(23001);
        }
        $post['type'] = empty($post['type']) ? 'image' : $post['type'];

        $dirname = !empty(self::$setting['attachment']['dirname']) ? trim(self::$setting['attachment']['dirname'], '/') : 'uploads';
        if (!empty(self::$setting['attachment']['dirrule'])) {
            $dirrule = str_replace(['{Y}', '{m}', '{d}'], [date('Y'), date('m'), date('d')], trim(self::$setting['attachment']['dirrule'], '/'));
        } else {
            $dirrule = date('Y/m');
        }
        $imgdir = CSPUBLIC . $dirname . '/' . $dirrule . '/';
        File::mkdir($imgdir);

        $not_allow = aval(self::$setting, 'security/uploadForbidFile', 'php|pl|cgi|asp|aspx|jsp|php3|shtm|shtml|js');
        $file_name = trim(preg_replace("#[ \r\n\t\*\%\\\/\?><\|\":]{1,}#", '', $post['files']['name']));
        if (!empty($file_name) && (preg_match("#\.(" . $not_allow . ")$#i", $file_name) || strpos($file_name, '.') === false)) {
            @unlink($post['files']['tmp_name']);
            return self::$output->withCode(23004);
        }

        //防止伪装成图片的木马上传
        $checkWords = aval(self::$setting, 'security/uploadCheckWords', '_GET|_POST|_COOKIE|assert|call_|create_|eval|_SERVER|function|defined|global|base64_');
        if (preg_match('/(' . $checkWords . ')/i', file_get_contents($post['files']['tmp_name']))) {
            @unlink($post['files']['tmp_name']);
            return self::$output->withCode(23005);
        }
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        //源文件类型检查
        $code = '';
        switch ($post['type']) {
            case 'image':
                if (strpos(self::$config['imgtype'], $ext) === false) {
                    $code = 23006;
                    break;
                }
                $info = getimagesize($post['files']['tmp_name']);
                //检测文件类型
                if (!is_array($info) || !in_array($info[2], [1, 2, 3, 6])) {
                    $code = 23001;
                }
                break;
            case 'flash':
                if ($ext != 'swf') {
                    $code = 23007;
                }
                break;
            case 'media':
                if (strpos(self::$config['mediatype'], $ext) === false) {
                    $code = 23008;
                }
                break;
            case 'addon':
                $subject = self::$config['imgtype'] . '|' . self::$config['mediatype'] . '|' . self::$config['softtype'];
                $allAllowType = str_replace('||', '|', $subject);
                if (strpos($allAllowType, $ext) === false) {
                    $code = 23009;
                }
                break;
            default:
                $code = 23010;
        }
        if (@filesize($post['files']['tmp_name']) > self::$config['maxUploadSize'] * 1024) {
            $code = 23012;
        }
        if ($code) {
            @unlink($post['files']['tmp_name']);
            return self::$output->withCode($code);
        }

        $filename = $imgdir . str_replace('.', '', uniqid(Ipdata::getip(), true)) . '.' . $ext;
        $uploadPost = [];
        $uploadPost['attachment'] = new UploadedFile($post['files']['tmp_name'], $post['files']['name'], $post['files']['type']);
        $upload = self::$request->getRequest()->withUploadedFiles($uploadPost)->getUploadedFiles();
        $upload['attachment']->moveTo($filename);
        //加水印或缩小图片
        if ($post['type'] == 'image') {
            Image::imageResize($filename, aval($post, 'width'), aval($post, 'height'));
            (!empty($post['water']) || !empty(self::$config['waterMark'])) && Image::waterImg($filename);
        }

        $fileurl = str_replace(CSPUBLIC, '/', $filename);
        //保存信息到数据库
        self::save($fileurl, 1);
        return self::$output->withCode(200)->withData(['fileurl' => $fileurl]);
    }

    /**
     * 上传附件记录入库
     * @param string $url
     * @param int $isfirst
     * @return int
     */
    public static function save(string $url, int $isfirst = 2): int
    {
        $dirname = !empty(self::$setting['attachment']['dirname']) ? trim(self::$setting['attachment']['dirname'], '/') : 'uploads';
        $data = [];
        $data['url'] = preg_replace("'(.*)?(/" . $dirname . "/(.*)){1}'isU", "\\2", $url);
        $p = pathinfo($url);
        if (preg_match("/jpg|jpeg|gif|png/i", $p['extension'])) {
            $data['mediatype'] = 1;
        } elseif ($p['extension'] == 'swf') {
            $data['mediatype'] = 2;
        } elseif (preg_match("/mp4|rmvb|rm|wmv|flv|mpg/i", $p['extension'])) {
            $data['mediatype'] = 3;
        } elseif (preg_match("/wav|mp3|wma|mov|amr|mid/i", $p['extension'])) {
            $data['mediatype'] = 4;
        } elseif (preg_match("/zip|gz|rar/i", $p['extension'])) {
            $data['mediatype'] = 5;
        } else {
            $data['mediatype'] = 6;
        }
        $file = CSPUBLIC . rtrim($url, '/');
        $p = @getimagesize($file);
        $data['width'] = $p[0];
        $data['height'] = $p[1];
        $data['filesize'] = @filesize($file);
        $data['isfirst'] = $isfirst == 1 ? 1 : 2;
        $data['createtime'] = TIMESTAMP;
        $data['ip'] = Ipdata::getip();
        return self::t('uploads')->insert($data, true);
    }

    /**
     * webupload上传
     * @param array $post
     * @return OutputInterface
     */
    public static function webupload(array $post): OutputInterface
    {
        isset($_SESSION) ? '' : @session_start();

        if (empty($post['fileid'])) {
            return self::$output->withCode(23001);
        }
        if (!empty($_SESSION['bigfile_info']) && count($_SESSION['bigfile_info']) >= 10) {
            return self::$output->withCode(23002);
        }
        $post['width'] = aval($post, 'width');
        $post['height'] = aval($post, 'height');
        $post['type'] = 'image';
        $result = self::upload($post);
        if ($result->getCode() != 200) {
            return $result;
        }

        $file = $result->getData();
        $img120 = Image::copyImage($file['fileurl'], 120, 120);
        $imagevariable = file_get_contents(CSPUBLIC . str_replace(self::$config['basehost'], '', $img120));

        //保存信息到 session
        if (!isset($_SESSION['file_info'])) {
            $_SESSION['file_info'] = [];
        }
        if (!isset($_SESSION['bigfile_info'])) {
            $_SESSION['bigfile_info'] = [];
        }
        $_SESSION['fileid'] = $post['fileid'];
        $_SESSION['bigfile_info'][$post['fileid']] = $file['fileurl'];
        $_SESSION['file_info'][$post['fileid']] = $imagevariable;
        $data = ['fileid' => $post['fileid'], 'imgurl' => $img120];
        return self::$output->withCode(200)->withData($data);
    }

    /**
     * 获取webupload上传的图片
     * @return OutputInterface
     */
    public static function getWebupload(): OutputInterface
    {
        $imgurls = [];
        isset($_SESSION) ? '' : @session_start();
        if (!empty($_SESSION['bigfile_info'])) {
            if (count($_SESSION['bigfile_info']) > 10) {
                $_SESSION['bigfile_info'] = [];
                foreach ($_SESSION['bigfile_info'] as $_v) {
                    self::uploadDel($_v['img']);
                }
                return self::$output->withCode(21045);
            }
            if (is_array($_SESSION['bigfile_info'])) {
                foreach ($_SESSION['bigfile_info'] as $_k => $_v) {
                    if ($imginfos = getimagesize(CSPUBLIC . ltrim($_v, '/'))) {
                        $key = md5($_v);
                        $imgurls[$key]['img'] = $_v;
                        $imgurls[$key]['text'] = self::input('picinfook' . $_k);
                        $imgurls[$key]['width'] = $imginfos[0];
                        $imgurls[$key]['height'] = $imginfos[1];
                    }
                }
            }
        }
        $_SESSION['bigfile_info'] = [];
        return self::$output->withCode(200)->withData($imgurls);
    }

    /**
     * 删除某一上传附件
     * @param string $url
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public static function uploadDel(string $url): OutputInterface
    {
        if (empty($url)) {
            return self::$output->withCode(21002);
        }
        if ($pics = self::listByUrl($url)) {
            $ids = [];
            foreach ($pics as $v) {
                $ids[] = $v['id'];
                $upfile = strpos($url, CSPUBLIC) === false ? CSPUBLIC . ltrim($v['url'], '/') : $v['url'];
                $upfile = realpath($upfile);
                if ($upfile && @is_file($upfile) && strpos($url, 'nopic') === false) {
                    @unlink($upfile);
                }
            }
            self::t('uploads')->withWhere(['id' => $ids])->delete();
        }
        return self::$output->withCode(200);
    }

    /**
     * 获取某附件及相关附件
     * @param string $url
     * @return array
     * @throws \SlimCMS\Error\TextException
     */
    private static function listByUrl(string $url): array
    {
        $url = str_replace(self::$config['basehost'], '', $url);
        $ext = self::$config['imgtype'] . '|' . self::$config['softtype'] . '|' . self::$config['mediatype'];
        if (empty($url) || preg_match('#http:\/\/#i', $url) || !preg_match('#\.(' . $ext . ')#', $url)) {
            return [];
        }
        if (strpos($url, '_')) {
            $url = preg_replace('#(.*)(_)?(\d+)?(x)?(\d+)?(\.(' . self::$config['imgtype'] . ')){1}#isU', '\\1', $url);
        } else {
            $url = preg_replace('#(.*)(\.(' . $ext . ')){1}#isU', '\\1', $url);
        }
        $url = str_replace("'", '', $url);
        $where = [];
        $where[] = self::t()->field('url', $url . '%', 'like');
        return self::t('uploads')->withWhere($where)->fetchList();
    }
}
