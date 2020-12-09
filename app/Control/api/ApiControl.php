<?php
/**
 * 接口控制类
 */
declare(strict_types=1);

namespace App\Control\api;

use App\Core\Forms;
use SlimCMS\Abstracts\ControlAbstract;
use slimCMS\Core\Request;
use slimCMS\Core\Response;
use App\Core\Wxxcx;
use SlimCMS\Core\Wxgzh;
use SlimCMS\Interfaces\OutputInterface;

class ApiControl extends ControlAbstract
{
    private $wxData = '';

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        if (!empty(self::$config['appid'])) {
            $data = ['appid' => self::$config['appid'], 'appsecret' => self::$config['appsecret']];
            $this->wxData = self::$output->withData($data);
        }
    }

    /**
     * 验证请求来路
     * @return OutputInterface
     */
    private static function verifyHttpReferer(): OutputInterface
    {
        if (!empty(self::$config['refererWhite'])) {
            $arr = array_map('trim', explode("\n", self::$config['refererWhite']));
            $str = str_replace('/', '\/', implode('|', $arr));
            if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^(' . $str . ')/i', $_SERVER['HTTP_REFERER'])) {
                return self::$output->withCode(21048);
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 获取OPENID
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function getOpenid()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $code = self::input('code');
        $output = $this->wxData->withData(['code' => $code]);
        $res = Wxxcx::getOpenid($output);

        //用户保存
        Wxxcx::userSave($res->getData());
        return $this->json($res);
    }

    /**
     * 解密用户信息
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function decryptData()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = self::input(['sessionkey' => 'string', 'encrypteddata' => 'string', 'iv' => 'string']);
        $output = $this->wxData->withData($data);
        $res = Wxxcx::decryptData($output);

        //用户保存
        Wxxcx::userSave($res->getData());
        return $this->json($res);
    }

    /**
     * 数据列表页
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataList()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int', 'order' => 'string', 'by' => 'string', 'ischeck' => 'int']);
        $param['inlistField'] = 'inlist';
        aval($param, 'order') != 'rand&#040;&#041;' && $param['cacheTime'] = 60;
        $res = Forms::dataList($param);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(1, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }
        unset($data['form'], $data['where'], $data['currenturl'], $data['get']);
        $res = self::$output->withCode(200)->withData($data);
        return $this->json($res);
    }

    /**
     * 数据统计
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataCount()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $param = self::input(['fid' => 'int']);
        $res = Forms::dataCount($param);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(8, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }
        unset($data['form']);
        $res = self::$output->withCode(200)->withData($data);
        return $this->json($res);
    }

    /**
     * 表单添加修改页
     * @return array|\cs090\core\数据|string
     */
    public function dataView()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $fid = (int)self::input('fid', 'int');
        $id = (int)self::input('id', 'int');
        $res = Forms::dataView($fid, $id);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(2, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }
        unset($data['form'], $data['fields']);
        $res = self::$output->withCode(200)->withData($data);
        return $this->json($res);
    }

    /**
     * 表单添加修改页
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataForm()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $fid = (int)self::input('fid', 'int');
        $id = self::input('id', 'int');
        $res = Forms::dataFormHtml($fid, $id);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(6, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }
        unset($data['fields'], $data['data']);
        $res = self::$output->withCode(200)->withData($data);
        return $this->json($res);
    }

    /**
     * 数据添加修改保存
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataSave()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $fid = (int)self::input('fid', 'int');
        $id = self::input('id', 'int');
        $res = Forms::formView($fid);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(3, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }
        $res = Forms::dataSave($fid, $id);
        return $this->json($res);
    }

    /**
     * 数据审核
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataCheck()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $fid = (int)self::input('fid', 'int');
        $ids = self::input('ids');
        $ids = $ids ? explode(',', $ids) : [];
        $ischeck = (int)self::input('ischeck', 'int');
        $res = Forms::formView($fid);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(7, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }
        $res = Forms::dataCheck($fid, $ids, $ischeck);
        return $this->json($res);
    }

    /**
     * 数据删除
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataDel()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $fid = (int)self::input('fid', 'int');
        $ids = self::input('ids');
        $ids = $ids ? explode(',', $ids) : [];
        $res = Forms::formView($fid);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(4, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }
        $res = Forms::dataDel($fid, $ids);
        return $this->json($res);
    }

    /**
     * 数据导出
     */
    public function dataExport()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }

        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int']);
        $res = Forms::formView($param['fid']);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $arr = explode(',', $data['form']['openapi']);
        if (!in_array(5, $arr)) {
            return $this->json(self::$output->withCode(211033));
        }

        $res = Forms::dataExport($param);
        $data = $res->getData();
        $response = self::$response->getResponse();
        if (self::input('down') == 1) {
            if (!is_file($data['file'])) {
                $output = self::$output->withCode(21050);
                return $this->directTo($output);
            }
            $file = fopen($data['file'], 'r');
            $filesize = filesize($data['file']);
            ob_end_clean();
            $response = $response
                ->withHeader('Content-type', 'application/octet-stream')
                ->withHeader('Accept-Ranges', 'bytes')
                ->withHeader('Accept-Length', $filesize)
                ->withHeader('Content-Disposition', 'attachment; filename=' . basename($data['file']));
            $content = fread($file, $filesize);
            fclose($file);
        } else {
            $response = $response->withHeader('Content-type', 'text/html');
            $content = $data['text'] . '<script>location="' . $res->getReferer() . '";</script>';
        }
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * 上传图片
     */
    public function uploadFile()
    {
        $res = self::verifyHttpReferer();
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $pic = self::input('name', 'img');
        $res = self::$output->withCode(200)->withData(['pic' => $pic]);
        return $this->json($res);
    }

    /**
     * 生成小程序二维码
     */
    public function qrcode()
    {
        $scene = self::input('scene');
        $width = self::input('width', 'int') ?: 430;
        $page = self::input('page');
        $param = ['scene' => $scene, 'width' => $width];
        if ($page) {
            $param['page'] = $page;
        }
        Wxxcx::getwxacodeunlimit($this->wxData->withData($param));
        $str = header('Content-type: image/jpeg');
        exit($str);
    }

    /**
     * 发送小程序订阅消息
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function sendTemplateMessage()
    {
        $param = self::input(['touser' => 'string', 'template_id' => 'string', 'data' => 'string']);
        $res = Wxxcx::sendTemplateMessage($this->wxData->withData($param));
        return $this->json($res);
    }

    /**
     * 获取公众号CODE
     */
    public function getCode()
    {
        $param = self::input(['scope' => 'string', 'redirect' => 'url']);
        $res = Wxgzh::getCode($this->wxData->withData($param));
        header('location:' . $res->getReferer());
        exit;
    }

    public function getUserInfo()
    {
        $param = self::input(['code' => 'string']);
        $res = Wxgzh::getUserInfo($this->wxData->withData($param));
        return $this->json($res);
    }

    /**
     * 生成微信分享签名
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function wxJsapiConfig()
    {
        $param = self::input(['url' => 'url']);
        $res = Wxgzh::wxJsapiConfig($this->wxData->withData($param));
        return $this->json($res);
    }
}