<?php
namespace DocumentService\Factory;

use DocumentService\Action\DocumentPreview;
use PSR\Container\ContainerInterface;

class DocumentPreviewFactory
{
    public function __invoke(ContainerInterface $container) : DocumentPreview
    {
        return new DocumentPreview($container->get('config')['documentService']);
    }
}