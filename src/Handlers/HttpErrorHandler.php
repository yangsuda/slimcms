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
        $statusCode = 500;
        $error = new Error(
            Error::SERVER_ERROR,
            'An internal error has occurred while processing your request.'
        );
        //return parent::respond();
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $error->setDescription($exception->getMessage());

            if ($exception instanceof HttpNotFoundException) {
                $error->setType(Error::RESOURCE_NOT_FOUND);
            } elseif ($exception instanceof HttpMethodNotAllowedException) {
                $error->setType(Error::NOT_ALLOWED);
            } elseif ($exception instanceof HttpUnauthorizedException) {
                $error->setType(Error::UNAUTHENTICATED);
            } elseif ($exception instanceof HttpForbiddenException) {
                $error->setType(Error::INSUFFICIENT_PRIVILEGES);
            } elseif ($exception instanceof HttpBadRequestException) {
                $error->setType(Error::BAD_REQUEST);
            } elseif ($exception instanceof HttpNotImplementedException) {
                $error->setType(Error::NOT_IMPLEMENTED);
            }
        }

        if (
            !($exception instanceof HttpException)
            && ($exception instanceof Exception || $exception instanceof Throwable)
            && $this->displayErrorDetails
        ) {
            $error->setDescription($exception->getMessage());
        }

        $output = new Output($statusCode, null, $error);
        $encodedOutput = json_encode($output, JSON_PRETTY_PRINT);

        $response = $this->responseFactory->createResponse($statusCode);

        if ($this->contentType !== null && array_key_exists($this->contentType, $this->errorRenderers)) {
            $response = $response->withHeader('Content-type', $this->contentType);
        } else {
            $response = $response->withHeader('Content-type', $this->defaultErrorRendererContentType);
        }
        $response->getBody()->write($encodedOutput);

        return $response;
    }
}
