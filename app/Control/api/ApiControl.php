<?php
/**
 * 接口控制类
 */
declare(strict_types=1);

namespace App\Control\api;

use App\Core\Forms;
use App\Model\main\AppsModel;
use SlimCMS\Abstracts\ControlAbstract;
use slimCMS\Core\Request;
use slimCMS\Core\Response;
use App\Core\Wxxcx;
use SlimCMS\Core\Wxgzh;
use SlimCMS\Error\TextException;
use SlimCMS\Helper\Crypt;
use SlimCMS\Interfaces\OutputInterface;

class ApiControl extends ControlAbstract
{
    private $wxData = null;

    public function __construct(Request $request, Response $response)
    {
        parent::__construct($request, $response);
        if (!empty(self::$config['appid'])) {
            $data = ['appid' => self::$config['appid'], 'appsecret' => self::$config['appsecret']];
            $this->wxData = self::$output->withData($data);
        } else {
            $this->wxData = self::$output;
        }
        $this->headNoCache();
    }

    /**
     * 请求方式测试
     * @param $method
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    private function requestCheck($method)
    {
        $server = self::$request->getRequest()->getServerParams();
        if ($server['REQUEST_METHOD'] != $method) {
            throw new TextException(21063);
        }
    }

    /**
     * 头部中设置不缓存数据
     */
    private function headNoCache()
    {
        self::$response->getResponse()
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Expires', '0');
    }

    /**
     * 权限检测
     * @return OutputInterface
     */
    private static function purviewCheck(...$identifier): OutputInterface
    {
        //验证请求来路
        if (!empty(self::$config['refererWhite'])) {
            $arr = array_map('trim', explode("\n", self::$config['refererWhite']));
            $str = str_replace('/', '\/', implode('|', $arr));
            if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^(' . $str . ')/i', $_SERVER['HTTP_REFERER'])) {
                return self::$output->withCode(21048);
            }
        }
        //token校验
        if (!empty(self::$config['tokenCheck'])) {
            $accessToken = self::inputString('accessToken');
            $res = AppsModel::checkAccessToken($accessToken, $identifier);
            if ($res->getCode() != 200) {
                return $res;
            }
        }
        return self::$output->withCode(200);
    }

    /**
     * 接口列表
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function doc()
    {
        isset($_SESSION) ? '' : session_start();
        $docAuth = (string)aval($_SESSION, 'docAuth');
        $appid = (string)Crypt::decrypt($docAuth);
        if (empty(self::$config['tokenCheck']) || !empty($appid)) {
            $identifier = self::inputString('identifier');
            self::$output = AppsModel::apiList($appid)->withData(['identifier' => $identifier, 'appid' => $appid]);
            if ($identifier) {
                self::$output = AppsModel::apiView($identifier);
                $data = self::$output->getData();
                if (empty($data['row'])) {
                    return self::$response->getResponse()
                        ->withHeader('location', self::url() . '&p=api/doc&identifier=c4466b3f51715045899b0b8bd142b23c');
                }
            }
            return $this->view(self::$output, __FUNCTION__);
        } else {
            return self::$response->getResponse()->withHeader('location', self::url() . '&p=api/docLogin');
        }
    }

    /**
     * 接口文档查看登陆
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws TextException
     */
    public function docLogin()
    {
        $formhash = self::input('formhash');
        if ($formhash) {
            $res = Forms::submitCheck($formhash);
            if ($res->getCode() != 200) {
                return $this->response($res);
            }
            $appid = self::inputString('appid');
            $res = AppsModel::docLogin($appid);
            if ($res->getCode() == 200) {
                isset($_SESSION) ? '' : session_start();
                $_SESSION['docAuth'] = Crypt::encrypt($appid);
                $res = $res->withReferer('?p=api/doc&identifier=c4466b3f51715045899b0b8bd142b23c');
            }
            return $this->response($res);
        }
        return $this->view(self::$output, __FUNCTION__);
    }

    /**
     * 退出
     * @return array
     */
    public function docLogout()
    {
        isset($_SESSION) ? '' : session_start();
        unset($_SESSION['docAuth']);
        $output = self::$output->withCode(200, 21047)->withReferer('?p=api/docLogin');
        return self::directTo($output);
    }

    /**
     * 错误代码
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function errCode()
    {
        isset($_SESSION) ? '' : session_start();
        $docAuth = (string)aval($_SESSION, 'docAuth');
        $appid = Crypt::decrypt($docAuth);
        if (empty(self::$config['tokenCheck']) || !empty($appid)) {
            self::$output = AppsModel::apiList($appid)->withData(['appid' => $appid, 'prompts' => self::$output->prompts()]);
            return $this->view(self::$output, __FUNCTION__);
        } else {
            return self::$response->getResponse()->withHeader('location', self::url() . '&p=api/docLogin');
        }
    }

    /**
     * 获取accessToken
     * @return OutputInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function getAccessToken()
    {
        $this->requestCheck('GET');
        if (empty(self::$config['tokenCheck'])) {
            $res = self::$output->withCode(223020);
            return $this->json($res);
        }
        $appid = self::inputString('appid');
        $appsecret = self::inputString('appsecret');
        $res = AppsModel::getAccessToken($appid, $appsecret);
        return $this->json($res);
    }

    /**
     * 获取OPENID
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function getOpenid()
    {
        $this->requestCheck('GET');
        $res = self::purviewCheck('api/' . __FUNCTION__);
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
        $this->requestCheck('GET');
        $res = self::purviewCheck('api/' . __FUNCTION__);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = self::input(['sessionkey' => 'string', 'encrypteddata' => 'string', 'iv' => 'string']);
        $output = $this->wxData->withData($data);
        $res = Wxxcx::decryptData($output);

        //用户保存
        $data = $res->getData();
        $data['openid'] = self::inputString('openid');
        !empty($data['openid']) && Wxxcx::userSave($data);
        return $this->json($res);
    }

    /**
     * 数据列表页
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \SlimCMS\Error\TextException
     */
    public function dataList()
    {
        $this->requestCheck('GET');
        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int', 'order' => 'string', 'by' => 'string', 'ischeck' => 'int']);
        $res = self::purviewCheck('api/' . __FUNCTION__, $param['fid'], 1);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $param['inlistField'] = 'inlist';
        aval($param, 'order') != 'rand&#040;&#041;' && $param['cacheTime'] = 60;
        $res = Forms::dataList($param);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        unset($data['form'], $data['where'], $data['currenturl'], $data['get'], $data['tags'], $data['fid'], $data['by']);
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
        $this->requestCheck('GET');
        $param = self::input(['fid' => 'int']);
        $res = self::purviewCheck('api/' . __FUNCTION__, $param['fid'], 8);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $res = Forms::dataCount($param);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
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
        $this->requestCheck('GET');
        $fid = self::inputInt('fid');
        $res = self::purviewCheck('api/' . __FUNCTION__, $fid, 2);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $id = self::inputInt('id');
        $fields = self::t('forms_fields')
            ->withWhere(['formid' => $fid, 'available' => 1, 'infront' => 1])
            ->onefieldList('identifier');
        $res = Forms::dataView($fid, $id, implode(',', $fields));
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
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
        $this->requestCheck('GET');
        $fid = self::inputInt('fid');
        $res = self::purviewCheck('api/' . __FUNCTION__, $fid, 6);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $id = self::inputInt('id');
        $res = Forms::dataFormHtml($fid, $id, ['infront' => true]);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $data = $res->getData();
        $val = [];
        foreach ($data['fieldshtml'] as $v) {
            $val[] = ['id' => $v['id'], 'title' => $v['title'], 'identifier' => $v['identifier'], 'field' => $v['field']];
        }
        $data = ['fieldshtml' => $val];
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
        $this->requestCheck('POST');
        $fid = self::inputInt('fid');
        $res = self::purviewCheck('api/' . __FUNCTION__, $fid, 3);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $id = self::inputInt('id');
        $res = Forms::formView($fid);
        if ($res->getCode() != 200) {
            return $this->json($res);
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
        $this->requestCheck('POST');
        $fid = self::inputInt('fid');
        $res = self::purviewCheck('api/' . __FUNCTION__, $fid, 7);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $ids = self::input('ids');
        $ids = $ids ? explode(',', $ids) : [];
        $ischeck = self::inputInt('ischeck');
        $res = Forms::formView($fid);
        if ($res->getCode() != 200) {
            return $this->json($res);
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
        $this->requestCheck('POST');
        $fid = self::inputInt('fid');
        $res = self::purviewCheck('api/' . __FUNCTION__, $fid, 4);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $ids = self::input('ids');
        $ids = $ids ? explode(',', $ids) : [];
        $res = Forms::formView($fid);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $res = Forms::dataDel($fid, $ids);
        return $this->json($res);
    }

    /**
     * 数据导出
     */
    public function dataExport()
    {
        $this->requestCheck('GET');
        $param = self::input(['fid' => 'int', 'page' => 'int', 'pagesize' => 'int']);
        $res = self::purviewCheck('api/' . __FUNCTION__, $param['fid'], 5);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $res = Forms::formView((int)$param['fid']);
        if ($res->getCode() != 200) {
            return $this->json($res);
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
        $this->requestCheck('POST');
        $res = self::purviewCheck('api/' . __FUNCTION__);
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
        $this->requestCheck('GET');
        $res = self::purviewCheck('api/' . __FUNCTION__);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
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
        $this->requestCheck('GET');
        $res = self::purviewCheck('api/' . __FUNCTION__);
        if ($res->getCode() != 200) {
            return $this->json($res);
        }
        $param = self::input(['touser' => 'string', 'template_id' => 'string', 'data' => 'string']);
        $res = Wxxcx::sendTemplateMessage($this->wxData->withData($param));
        return $this->json($res);
    }

    /**
     * 获取公众号CODE
     */
    public function getCode()
    {
        $this->requestCheck('GET');
        $param = self::input(['scope' => 'string', 'redirect' => 'url']);
        $res = Wxgzh::getCode($this->wxData->withData($param));
        header('location:' . $res->getReferer());
        exit;
    }

    /**
     * 获取微信用户信息
     * @return array|\Psr\Http\Message\ResponseInterface
     */
    public function getUserInfo()
    {
        $this->requestCheck('GET');
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
        $this->requestCheck('GET');
        $param = self::input(['url' => 'url']);
        $res = Wxgzh::wxJsapiConfig($this->wxData->withData($param));
        return $this->json($res);
    }
}
