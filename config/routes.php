<?php

declare(strict_types=1);

use Auth\Action\AuthCheck;
use Auth\Action\AuthSSL;
use Auth\Action\NeedsAuth;
use DocumentService\Action\ApiPing;
use DocumentService\Action\DocumentPreview;
use DocumentService\Action\Info;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;

/**
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/:id', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    $app->route('/v2/ping', ApiPing::class, ['POST', 'GET'], 'apiPing');
    $app->route('/v2/ping/auth', [NeedsAuth::class, AuthSSL::class, AuthCheck::class, ApiPing::class], ['POST', 'GET'], 'apiPingAuth');
    $app->route('/v2/documentPreviewService', [NeedsAuth::class, AuthSSL::class, AuthCheck::class, DocumentPreview::class],['POST'], 'documentPreviewService');
    $app->route('/v2/info', Info::Class, 'info');
};
