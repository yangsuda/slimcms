<?php
declare(strict_types=1);

namespace SlimCMS\Handlers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler;
use SlimCMS\Core\Error;
use SlimCMS\Core\Output;
use SlimCMS\Error\JsonError;
use SlimCMS\Error\PlainTextError;
use SlimCMS\Error\HtmlError;
use SlimCMS\Error\XmlError;
use SlimCMS\Error\TextException;
use Throwable;

class HttpErrorHandler extends ErrorHandler
{
    /**
     * {@inheritdoc}
     */
    protected $errorRenderers = [
        'application/json' => JsonError::class,
        'application/xml' => XmlError::class,
        'text/xml' => XmlError::class,
        'text/html' => HtmlError::class,
        'text/plain' => PlainTextError::class,
    ];

    /**
     * @inheritdoc
     */
    protected function respond(): Response
    {

        $exception = $this->exception;

        if ($exception instanceof TextException) {
            $result = ['code' => $exception->getCode(), 'msg' => $exception->getMessage()];
            $encodedOutput = json_encode($result, JSON_PRETTY_PRINT);
            $response = $this->responseFactory->createResponse();
            if ($this->contentType !== null && array_key_exists($this->contentType, $this->errorRenderers)) {
                $response = $response->withHeader('Content-type', $this->contentType);
            } else {
                $response = $response->withHeader('Content-type', $this->defaultErrorRendererContentType);
            }
            $response->getBody()->write($encodedOutput);
            return $response;
        }else{
            return parent::respond();
        }
    }
}
