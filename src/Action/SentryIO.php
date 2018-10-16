<?php
namespace DocumentService\Action;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Raven_Client;
use Raven_ErrorHandler;
use Psr\Http\Message\ResponseInterface;

class SentryIO implements MiddlewareInterface
{
    private $sentryURL;

    public function __construct(string $sentryURL)
    {
        $this->sentryURL = $sentryURL;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
        $client = new Raven_Client($this->sentryURL);
        $error_handler = new Raven_ErrorHandler($client);
        $error_handler->registerExceptionHandler();
        $error_handler->registerErrorHandler();
        $error_handler->registerShutdownFunction();

        $client->user_context(array(
            'request' => $request,
        ));

        return $delegate->handle($request);
    }
}
