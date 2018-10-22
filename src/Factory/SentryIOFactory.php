<?php namespace DocumentService\Factory;

use Psr\Container\ContainerInterface;
use DocumentService\Action\SentryIO;

class SentryIOFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $sentry_url = '';
        if (key_exists('sentryio', $container->get('config')) && isset($container->get('config')['sentryio'])) {
            $sentry_url = $container->get('config')['sentryio'];
        }
        if ($sentry_url == null) {
            $sentry_url = '';
        }
        return new SentryIO($sentry_url);
    }
}
