<?php declare(strict_types=1);

namespace DocumentService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'documentService' => [
                "tempDir" => "temp/",
                "downDir" => "download/",
                "downUrl" => "https://download.invalid",
                "maxProc" => 4,
                "loggerOut" => "doc.log",
                'authLogger' => 'auth.log',
                "ext" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot',
                    'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx', 'pdf', 'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
                "docExt" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot',
                    'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx',
                    ],
                "imgExt" => [
                    'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
                "ooBinary"=>'soffice',
            ],
            'auth' => [
                // default authentication, if no auth is configured auth will fail
                'default' => ['name' => 'default', 'permission' => "false",],
                'apiPingAuth' => ['name' => 'apiPingAuth', 'permission' => '(1=1)', 'required' => true],
                'documentPreviewService' =>
                    ['name' => 'documentPreviewService', 'permission' => '(1=1)', 'required' => false],
            ],
        ];
    }


    public function getDependencies(): array
    {
        return [
            'invokables' => [
                Action\ApiPing::class => Action\ApiPing::class,
            ],
            'factories'  => [
                Action\SentryIO::class => Factory\SentryIOFactory::class,
                Action\DocumentPreview::class => Factory\DocumentPreviewFactory::class,
            ],
        ];
    }
}
