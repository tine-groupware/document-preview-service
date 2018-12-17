<?php declare(strict_types=1);

namespace DocumentService\Action;

use DocumentService\Lock;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class Info implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        $lock = new Lock(false, 4, 4);
        return new JsonResponse(['locks' => $lock->currentLocks()]);
    }
}