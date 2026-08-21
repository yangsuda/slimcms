<?php
/**
 * 阿里云OSS上传类，替换方式：
 * 1、加载阿里云SDK:composer require aliyuncs/oss-sdk-php
 * 2、替换上传类：将app/Core/settings.php中Upload()改成AliOSS()，注意引用要改一下
 * @author zhucy
 */

declare(strict_types=1);

namespace app\Service\aliyun;

use OSS\Core\OssException;
use OSS\OssClient;
use Slim\App;
use Slim\Psr7\UploadedFile;
use SlimCMS\Abstracts\ServiceAbstract;
use SlimCMS\Core\Request;
use SlimCMS\Helper\Ipdata;
use SlimCMS\Helper\File;
use SlimCMS\Interfaces\OutputInterface;
use SlimCMS\Interfaces\UploadInterface;

class AliOssService extends ServiceAbstract implements UploadInterface
{
    private $setting;//站点初始化参数
    private array $config;//后台配置参数

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->setting = $this->container->get('settings');
        $this->config = $this->container->get('cfg');
    }

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

    protected function getSaveDir(string $dirrule = null): string
    {
        $dir = !empty($this->setting['attachment']['dirname']) ? trim($this->setting['attachment']['dirname'], '/') : 'uploads';
        if (!isset($dirrule)) {
            if (!empty($this->setting['attachment']['dirrule'])) {
                $dirrule = str_replace(
                    ['{Y}', '{m}', '{d}'],
                    [date('Y'), date('m'), date('d')],
                    trim($this->setting['attachment']['dirrule'], '/'));
            } else {
                $dirrule = date('Y/m');
            }
        }
        return $dir . '/' . ($dirrule ? $dirrule . '/' : '');
    }

    /**
     * @inheritDoc
     */
    public function h5(string $img): OutputInterface
    {
        if (preg_match('/^data:\s*([^\/]+)\/([^\/]+);base64,/', $str, $matches)) {
            $str = preg_replace('/^data:image\/\w+;base64,/', '', $str);
            $data = base64_decode($str);
            if (empty($data)) {
                return self::$output->withCode(27013);
            }

            //防止伪装成图片的木马上传
            $checkWords = aval($this->setting, 'security/uploadCheckWords');
            if (!empty($checkWords) && preg_match('/(' . $checkWords . ')/i', $data)) {
                return self::$output->withCode(23005);
            }

            $dirname = $this->getSaveDir('tmp');
            $file = uniqid() . '.' . $matches[2];
            $tmpPath = CSPUBLIC . $dirname;
            File::mkdir($tmpPath);
            $fileUrl = $tmpPath . $file;
            $success = file_put_contents($fileUrl, $data);
            if (!$success) {
                return self::$output->withCode(23014);
            }

            if (in_array($matches[2], explode('|', $this->config['mediatype']))) {
                $types = 'media';
            } elseif (in_array($matches[2], explode('|', $this->config['imgtype']))) {
                $types = 'image';
            } else {
                $types = 'addon';
            }
            $mimeType = $matches[1] . '/' . $matches[2]; // 提取 MIME 类型
            $uploadFile = new UploadedFile($fileUrl, $file, $mimeType, filesize($fileUrl));
            return $this->upload($uploadFile, $types);
        }
        return $this->output->withCode(27013);
    }

    /**
     * @inheritDoc
     */
    public function upload(UploadedFile $post, string $type = 'image', string $dir = null): OutputInterface
    {
        $not_allow = aval($this->setting, 'security/uploadForbidFile', 'php|pl|cgi|asp|aspx|jsp|php3|shtm|shtml|js');
        $file_name = trim(preg_replace("#[ \r\n\t\*\%\\\/\?><\|\":]{1,}#", '', $post->getClientFilename()));
        if (!empty($file_name) && (preg_match("#\.(" . $not_allow . ")$#i", $file_name) || strpos($file_name, '.') === false)) {
            @unlink($post->getFilePath());
            return $this->output->withCode(23004);
        }
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $dirname = !empty($this->setting['attachment']['dirname']) ? trim($this->setting['attachment']['dirname'], '/') : 'uploads';
        if (!empty($this->setting['attachment']['dirrule'])) {
            $dirrule = str_replace(
                ['{Y}', '{m}', '{d}'],
                [date('Y'), date('m'), date('d')],
                trim($this->setting['attachment']['dirrule'], '/')
            );
        } else {
            $dirrule = date('Y/m');
        }
        $imgdir = $dirname . '/' . $dirrule . '/';

        $object = $imgdir . str_replace('.', '', uniqid(Ipdata::getip(), true)) . '.' . $ext;
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $res = $ossClient->uploadFile($this->bucket, $object, $post->getFilePath());
            @unlink($post->getFilePath());
            return $this->output->withCode(200)->withData(['fileurl' => $res['info']['url']]);
        } catch (OssException $e) {
            return $this->output->withCode(21000, $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function webupload(UploadedFile $file, array $option = []): OutputInterface
    {
        if (empty($file)) {
            return $this->output->withCode(23001);
        }
        $session = $this->session();
        if ($session->has('bigfile_info') && count($session->get('bigfile_info')) >= 10) {
            return $this->output->withCode(23002);
        }
        $result = $this->upload($file,'image');
        if ($result->getCode() != 200) {
            return $result;
        }

        $file = $result->getData();

        //保存信息到 session
        $bigfile_info = $session->get('bigfile_info');
        $bigfile_info[$option['fileid']] = $file['fileurl'];
        $session->set('bigfile_info', $bigfile_info);
        $session->set('fileid', $option['fileid']);
        $data = ['fileid' => $option['fileid'], 'imgurl' => $file['fileurl'] . '?x-oss-process=image/resize,m_fill,w_120,h_120/quality,q_100'];
        return $this->output->withCode(200)->withData($data);
    }

    /**
     * @inheritDoc
     */
    public function getWebupload(): OutputInterface
    {
        $imgurls = [];
        $session = $this->session();
        if ($session->has('bigfile_info')) {
            if (count($session->get('bigfile_info')) > 10) {
                foreach ($session->get('bigfile_info') as $_v) {
                    $this->uploadDel($_v['img']);
                }
                return $this->output->withCode(21045);
            }
            if (is_array($session->get('bigfile_info'))) {
                $upload = $this->container->get(UploadInterface::class);
                foreach ($session->get('bigfile_info') as $_k => $_v) {
                    $info = $upload->metaInfo($_v, 'url,width')->getData();
                    if (!empty($info)) {
                        $key = md5($_v);
                        $imgurls[$key]['img'] = $_v;
                        $imgurls[$key]['text'] = $this->i(Request::class)->input('picinfook' . $_k);
                        $imgurls[$key]['width'] = $info['width'];
                        $imgurls[$key]['height'] = $info['height'];
                    }
                }
            }
        }
        $session->delete('bigfile_info');
        return $this->output->withCode(200)->withData($imgurls);
    }

    /**
     * @inheritDoc
     */
    public function uploadDel(string $url): OutputInterface
    {
        if (empty($url)) {
            return $this->output->withCode(21002);
        }
        try {
            $parse = parse_url($url);
            $url = trim($parse['path'], '/');
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->deleteObject($this->bucket, $url);
            return $this->output->withCode(200);
        } catch (OssException $e) {
            return $this->output->withCode(21000, $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function metaInfo(string $url, string $info = 'url,size'): OutputInterface
    {
        if (empty($url)) {
            return $this->output->withCode(21002);
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
            return $this->output->withCode(200)->withData($data);
        } catch (OssException $e) {
            return $this->output->withCode(21000, $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function copyImage(string $pic, int $width = 2000, int $height = 2000, $more = []): string
    {
        $style = aval($more, 'style', '/auto-orient,1/quality,q_100');
        $nopic = $this->config['basehost'] . aval($more, 'nopic', 'resources/global/images/nopic/nopic.jpg');
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

    public function superFileUpload(array $file, int $index, string $filename, string $diyDir = ''): OutputInterface
    {

    }

}
