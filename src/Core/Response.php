<?php
/**
 * 数据输出处理类
 * @author zhucy
 */

declare(strict_types=1);

namespace SlimCMS\Core;

use Psr\Http\Message\ResponseInterface;

class Response extends Message
{
    private $jsonCallbackStr = '';

    /**
     * 返回提示数据
     * @param $code
     * @return array
     */
    public function output($result): ResponseInterface
    {
        if (is_numeric($result)) {
            $result = ['code' => $result, 'msg' => $this->promptMsg($result), 'data' => [], 'referer' => ''];
        }
        if ($this->jsonCallbackStr) {
            $encodedOutput = $this->jsonCallbackStr . '(' . json_encode($result) . ')';
        } else {
            $contentType = $this->determineContentType();
            $this->response = $this->response->withHeader('Content-type', $contentType);
            if (strpos($contentType, 'json')) {
                $encodedOutput = json_encode($result, JSON_PRETTY_PRINT);
            } else {
                $encodedOutput = $this->responseText($result);
            }
        }
        $this->response->getBody()->write($encodedOutput);
        return $this->response;
    }

    /**
     * 返回提示代码对应信息
     * @param $code
     * @param array $para
     * @return mixed|string
     */
    private function promptMsg($code, $para = array()): string
    {
        $prompt = require CSROOT . 'config/prompt.php';
        $prompt += require dirname(dirname(__FILE__)) . '/Config/prompt.php';
        $str = $prompt[$code];
        if ($para) {
            if (is_array($para)) {
                extract($para);
                eval("\$str = \"$str\";");
            } elseif (is_string($para)) {
                $str = $para;
            } elseif (is_numeric($para)) {
                $str = $this->promptMsg($para);
            }
        }
        return $str;
    }

    /**
     * 获取文本内容类型
     * @return string
     */
    private function determineContentType(): ?string
    {
        $accept = ['application/json', 'application/xml', 'text/xml', 'text/html', 'text/plain'];
        $acceptHeader = $this->request->getHeaderLine('Accept');
        $selectedContentTypes = array_intersect(
            explode(',', $acceptHeader),
            $accept
        );
        $count = count($selectedContentTypes);

        if ($count) {
            $current = current($selectedContentTypes);

            /**
             * Ensure other supported content types take precedence over text/plain
             * when multiple content types are provided via Accept header.
             */
            if ($current === 'text/plain' && $count > 1) {
                return next($selectedContentTypes);
            }

            return $current;
        }

        if (preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];
            if (array_key_exists($mediaType, $accept)) {
                return $mediaType;
            }
        }

        return null;
    }

    /**
     * JSONP数据返回
     * @param $jsonCallbackStr
     * @return $this
     */
    public function jsonCallback($jsonCallbackStr)
    {
        $this->jsonCallbackStr = $jsonCallbackStr;
        return $this;
    }

    /**
     * 返回提示消息
     * @param $msg
     */
    protected function responseText(array $result): string
    {
        $showType = aval($result, 'showType', 3);

        if ($showType == 1) {
            self::$cookie->set('errorCode', $result['code']);
            self::$cookie->set('errorMsg', $result['msg']);
            $this->response = $this->response->withHeader('location', $result['referer']);
            return '';
        } elseif ($showType == 2) {
            return "<script>alert(\"" . $result['msg'] . "\");</script>";
        } else {
            $cfg = $this->cfg;
            return <<<EOT
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$cfg['webname']}后台管理系统</title>
    <link href="{$cfg['basehost']}favicon.ico" type="image/x-icon" rel="shortcut icon">
    <link href="{$cfg['resourceUrl']}assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="{$cfg['resourceUrl']}assets/css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div class="card text-center" style="width: 22rem;margin: 10rem auto">
    <div class="card-header">
        {$cfg['webname']}提示信息
    </div>
    <div class="card-body">
        <p class="card-text text-dark">{$result['msg']}</p>
        <a href="{$result['referer']}" class="text-info">如果你的浏览器没反应，请点击这里...</a>
    </div>
</div>
<script>setTimeout("location='{$result['referer']}';",3000);</script>
</body>
</html>
EOT;
        }
    }
}