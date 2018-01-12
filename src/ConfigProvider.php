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
                "downUrl" => "https://download.invalid",
                "maxProc" => 4,
                "loggerOut" => "doc.log",
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
            'routes' => [
                [
                    'name' => 'routeName',
                    'path' => '/tine20/documentPreview',
                    'middleware' => [
                        Auth\Action\NeedsAuth::class,
                        Auth\Action\AuthSSL::class,
                        Auth\Action\AuthCheck::class,
                        DocumentService\Action\DocumentPreview::class,
                    ],
                    'allowed_methods' => ['POST'],
                    'auth' =>[
                        'required' => true,
                        'permission' => '(1=1)'
                    ]
                ],
            ],
            'auth' => [
                'default' => ['name' => 'default', 'permission' => "false",]
            ],
            'authLogger' => 'auth.log',
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
