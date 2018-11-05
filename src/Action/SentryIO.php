<?php declare(strict_types=1);

namespace DocumentService\Action;

use DocumentService\ErrorHandler;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Raven_Client;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\TextResponse;

class SentryIO implements MiddlewareInterface
{
    private $_sentryURL;

    public function __construct(string $sentryURL)
    {
        $this->_sentryURL = $sentryURL;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        $client = new Raven_Client($this->_sentryURL);

        $client->install();

        $client->user_context(
            array(
            'request' => $request,
            )
        );

        (ErrorHandler::getInstance())->setSentryClient($client);

        return $delegate->handle($request);
    }
}
