<?php declare(strict_types=1);

namespace DocumentService\Action;

use DocumentService\ErrorHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Raven_Client;
use Psr\Http\Message\ResponseInterface;

class SentryIO implements MiddlewareInterface
{
    private $sentryURL;

    public function __construct(string $sentryURL)
    {
        $this->sentryURL = $sentryURL;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $delegate
     * @return ResponseInterface
     * @throws \Raven_Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        $client = new Raven_Client($this->sentryURL);

        $client->install();

        include(getcwd() . '/buildnumber');

        $client->setRelease($buildNumber);

        $client->user_context(
            array(
            'request' => var_export($request, true),
            )
        );

        (ErrorHandler::getInstance())->setSentryClient($client);

        return $delegate->handle($request);
    }
}
