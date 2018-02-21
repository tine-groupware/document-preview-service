<?php
namespace DocumentService\Factory;

use DocumentService\Action\SentryIO;
use Psr\Container\ContainerInterface;

class SentryIOFactory
{
    public function __invoke(ContainerInterface $container) : SentryIO
    {
        return new SentryIO($container->get('config')['sentryio']);
    }
}
