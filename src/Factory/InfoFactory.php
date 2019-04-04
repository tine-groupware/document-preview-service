<?php namespace DocumentService\Factory;

use DocumentService\Action\Info;
use PSR\Container\ContainerInterface;

class InfoFactory
{
    /**
     * @param ContainerInterface $container
     * @return Info
     * @throws \DocumentService\DocumentPreviewException
     */
    public function __invoke(ContainerInterface $container)
    {
        return new Info($container->get('config')['documentService']);
    }
}
