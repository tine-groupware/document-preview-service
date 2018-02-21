<?php
namespace DocumentService\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Raven_Client;
use Raven_ErrorHandler;

class SentryIO implements MiddlewareInterface
{
    private $sentryURL;

    public function __construct(string $sentryURL)
    {
        $this->sentryURL = $sentryURL;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $client = new Raven_Client($this->sentryURL);
        $error_handler = new Raven_ErrorHandler($client);
        $error_handler->registerExceptionHandler();
        $error_handler->registerErrorHandler();
        $error_handler->registerShutdownFunction();

        $client->user_context(array(
            'request' => $request,
        ));

        return $delegate->process($request);
    }
}
