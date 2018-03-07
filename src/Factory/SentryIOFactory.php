<?php
namespace DocumentService\Factory;

use Psr\Container\ContainerInterface;
use DocumentService\Action\SentryIO;

class SentryIOFactory
{
    public function __invoke(ContainerInterface $container) : SentryIO
    {
        $sentry_url = $container->get('config')['sentryio'];
        if ($sentry_url == null) $sentry_url = '';
        return new SentryIO($sentry_url);
    }
}
