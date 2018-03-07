<?php
namespace DocumentService\Factory;

use Psr\Container\ContainerInterface;

class SentryIOFactory
{
    public function __invoke(ContainerInterface $container) : DocumentService\Action\SentryIO
    {
        return new DocumentService\Action\SentryIO($container->get('config')['sentryio'], '');
    }
}
