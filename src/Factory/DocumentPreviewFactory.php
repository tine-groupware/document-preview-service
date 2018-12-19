<?php namespace DocumentService\Factory;

use DocumentService\Action\DocumentPreview;
use PSR\Container\ContainerInterface;

class DocumentPreviewFactory
{
    /**
     * @param ContainerInterface $container
     * @return DocumentPreview
     * @throws \DocumentService\DocumentPreviewException
     */
    public function __invoke(ContainerInterface $container)
    {
        return new DocumentPreview($container->get('config')['documentService']);
    }
}
