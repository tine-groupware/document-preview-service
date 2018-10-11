<?php namespace DocumentService\Action;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\TextResponse;

class ApiPing implements MiddlewareInterface {

    public function process(ServerRequestInterface $request, DelegateInterface $delegate){
        $bn = file_get_contents('./buildnumber');
        return new TextResponse("$bn");
    }
}