<?php

namespace DocumentService;


class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
            'documentService' => [
                "tempDir" => "temp/",
                "downDir" => "download/",
                "downUrl" => "http://nsa.cloud/",
                "maxProc" => 4,
                "loggerOut" => "log", //file or zendLogger
                "ext" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx', 'pdf', 'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
                "docExt" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx',
                    ],
                "imgExt" => [
                    'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
            ],
        ];
    }

    public function getDependencies()
    {
        return [
            'factories'  => [
                Action\DocumentPreview::class => Factory\DocumentPreviewFactory::class,
            ],
        ];
    }
}