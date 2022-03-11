<?php
/**
 * 阿里云OSS上传类，替换方式：
 * 1、加载阿里云SDK:composer require aliyuncs/oss-sdk-php
 * 2、替换上传类：将app/Core/settings.php中Upload()改成AliOSS()，注意引用要改一下
 * @author zhucy
 */

declare(strict_types=1);

namespace App\Model\aliyun;

use OSS\Core\OssException;
use OSS\OssClient;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Helper\File;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Interfaces\UploadInterface;
use SlimCMS\Abstracts\ModelAbstract;

class AliOss extends ModelAbstract implements UploadInterface
{

    /**
     * RAM账号，创建地址：https://ram.console.aliyun.com/users
     * @var string
     */
    private $accessKeyId = '';

    /**
     * RAM密码
     * @var string
     */
    private $accessKeySecret = '';
    /**
     * Endpoint以杭州为例，其它Region请按实际情况填写。
     * @var string
     */
    private $endpoint = 'http://oss-cn-hangzhou.aliyuncs.com';
    /**
     * 存储空间名称
     * @var string
     */
    private $bucket = '';

    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function h5(string $img): OutputInterface
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
        return $this->upload($post);
    }

    /**
     * @inheritDoc
     */
    public function upload(array $post): OutputInterface
    {
        if (empty($post['files']['tmp_name'])) {
            return self::$output->withCode(23001);
        }
        $post['type'] = empty($post['type']) ? 'image' : $post['type'];

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

        $filename = $post['files']['tmp_name'];

        $dirname = !empty(self::$setting['attachment']['dirname']) ? trim(self::$setting['attachment']['dirname'], '/') : 'uploads';
        if (!empty(self::$setting['attachment']['dirrule'])) {
            $dirrule = str_replace(
                ['{Y}', '{m}', '{d}'],
                [date('Y'), date('m'), date('d')],
                trim(self::$setting['attachment']['dirrule'], '/')
            );
        } else {
            $dirrule = date('Y/m');
        }
        $imgdir = $dirname . '/' . $dirrule . '/';

        $object = $imgdir . str_replace('.', '', uniqid(Ipdata::getip(), true)) . '.' . $ext;
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $res = $ossClient->uploadFile($this->bucket, $object, $filename);
            @unlink($filename);
            return self::$output->withCode(200)->withData(['fileurl' => $res['info']['url']]);
        } catch (OssException $e) {
            return self::$output->withCode(21000, ['msg' => $e->getMessage()]);
        }
    }

    /**
     * @inheritDoc
     */
    public function webupload(array $post): OutputInterface
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
        $result = $this->upload($post);
        if ($result->getCode() != 200) {
            return $result;
        }

        $file = $result->getData();
        $img120 = $file['fileurl'] . '?x-oss-process=image/resize,m_fill,w_120,h_120/quality,q_100';
        $imagevariable = file_get_contents($img120);

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
     * @inheritDoc
     */
    public function getWebupload(): OutputInterface
    {
        $imgurls = [];
        isset($_SESSION) ? '' : @session_start();
        if (!empty($_SESSION['bigfile_info'])) {
            if (count($_SESSION['bigfile_info']) > 10) {
                $_SESSION['bigfile_info'] = [];
                foreach ($_SESSION['bigfile_info'] as $_v) {
                    $this->uploadDel($_v['img']);
                }
                return self::$output->withCode(21045);
            }
            if (is_array($_SESSION['bigfile_info'])) {
                $upload = self::$container->get(UploadInterface::class);
                foreach ($_SESSION['bigfile_info'] as $_k => $_v) {
                    $info = $upload->metaInfo($_v, 'url,width')->getData();
                    if (!empty($info)) {
                        $key = md5($_v);
                        $imgurls[$key]['img'] = $_v;
                        $imgurls[$key]['text'] = self::input('picinfook' . $_k);
                        $imgurls[$key]['width'] = $info['width'];
                        $imgurls[$key]['height'] = $info['height'];
                    }
                }
            }
        }
        $_SESSION['bigfile_info'] = [];
        return self::$output->withCode(200)->withData($imgurls);
    }

    /**
     * @inheritDoc
     */
    public function uploadDel(string $url): OutputInterface
    {
        if (empty($url)) {
            return self::$output->withCode(21002);
        }
        try {
            $parse = parse_url($url);
            $url = trim($parse['path'], '/');
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->deleteObject($this->bucket, $url);
            return self::$output->withCode(200);
        } catch (OssException $e) {
            return self::$output->withCode(21000, ['msg' => $e->getMessage()]);
        }
    }

    /**
     * @inheritDoc
     */
    public function metaInfo(string $url, string $info = 'url,size'): OutputInterface
    {
        if (empty($url)) {
            return self::$output->withCode(21002);
        }
        $parse = parse_url($url);
        $url = trim($parse['path'], '/');
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            // 获取文件的全部元信息。
            $objectMeta = $ossClient->getObjectMeta($this->bucket, $url);

            $data = [];
            $arr = explode(',', $info);
            if (in_array('url', $arr)) {
                $data['url'] = $objectMeta['info']['url'];
            }
            if (in_array('size', $arr)) {
                $data['size'] = $objectMeta['info']['download_content_length'];
            }
            if (in_array('width', $arr) || in_array('height', $arr)) {
                $info = getimagesize($objectMeta['info']['url']);
                $data['width'] = $info[0];
                $data['height'] = $info[1];
            }
            return self::$output->withCode(200)->withData($data);
        } catch (OssException $e) {
            return self::$output->withCode(21000, ['msg' => $e->getMessage()]);
        }
    }

    /**
     * @inheritDoc
     */
    public function copyImage(string $pic, int $width = 2000, int $height = 2000, $more = []): string
    {
        $style = aval($more, 'style', '/auto-orient,1/quality,q_100');
        $nopic = self::$config['basehost'] . aval($more, 'nopic', 'resources/global/images/nopic/nopic.jpg');
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $parse = parse_url($pic);
            $url = trim($parse['path'], '/');
            if ($ossClient->doesObjectExist($this->bucket, $url)) {
                return $pic . '?x-oss-process=image/resize,m_fill,w_' . $width . ',h_' . $height . $style;
            }
            return $nopic;
        } catch (OssException $e) {
            return $nopic;
        }
    }

    public function superFileUpload(array $file, int $index, string $filename): OutputInterface
    {

    }

}
