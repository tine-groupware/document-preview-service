<?php
namespace DocumentService\Factory;

use Psr\Container\ContainerInterface;
use DocumentService\Action;

class SentryIOFactory
{
    public function __invoke(ContainerInterface $container) : Action\SentryIO
    {
        $sentry_url = $container->get('config')['sentryio'];
        if ($sentry_url == null) $sentry_url = '';
        return new Action\SentryIO($sentry_url);
    }
}
