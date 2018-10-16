<?php namespace DocumentService\Action;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

class ApiPing implements MiddlewareInterface {

    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface {
        return new JsonResponse(['ack' => time()]);
    }
}