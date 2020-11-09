<?php
declare(strict_types=1);

namespace SlimCMS\Handlers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Handlers\ErrorHandler;
use SlimCMS\Core\Error;
use SlimCMS\Error\JsonError;
use SlimCMS\Error\PlainTextError;
use SlimCMS\Error\HtmlError;
use SlimCMS\Error\XmlError;
use SlimCMS\Error\TextException;

class HttpErrorHandler extends ErrorHandler
{
    /**
     * {@inheritdoc}
     */
    protected $defaultErrorRenderer = HtmlError::class;

    /**
     * {@inheritdoc}
     */
    protected $logErrorRenderer = PlainTextError::class;

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
            $encodedOutput = json_encode($exception->getResult(), JSON_PRETTY_PRINT);
            $response = $this->responseFactory->createResponse();
            if ($this->contentType !== null && array_key_exists($this->contentType, $this->errorRenderers)) {
                $response = $response->withHeader('Content-type', $this->contentType);
            } else {
                $response = $response->withHeader('Content-type', $this->defaultErrorRendererContentType);
            }
            $response->getBody()->write($encodedOutput);
            return $response;
        }
        return parent::respond();
    }

    /**
     * {@inheritDoc}
     */
    protected function logError(string $error): void
    {
        if ($this->exception instanceof TextException) {
            $this->logger = $this->logger->withName($this->exception->getLoggerName());
            $error = $this->exception->getResult()->getMsg().' '.$error;
            $this->logger->alert($error);
        } else {
            $this->logger->error($error);
        }
    }
}
