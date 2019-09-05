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
                "extToMime" => [
                    'txt' => ['text/plain'],
                    'rtf' => ['text/rtf'],
                    'odt' => ['application/vnd.oasis.opendocument.text'],
                    'ott' => ['application/vnd.oasis.opendocument.text-template'],
                    'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
                    'ots' => ['application/vnd.oasis.opendocument.spreadsheet-template'],
                    'odp' => ['application/vnd.oasis.opendocument.presentation'],
                    'otp' => ['application/vnd.oasis.opendocument.presentation-template'],
                    'xls' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    'xlt' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
                    'xltx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
                    'doc' => ['application/msword'],
                    'dot' => ['application/msword'],
                    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    'dotx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    'ppt' => ['application/vnd.ms-powerpoint'],
                    'pot' => ['application/vnd.ms-powerpoint'],
                    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
                    'potx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
                    'pdf' => ['application/pdf'],
                    'jpg' => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'gif' => ['image/gif'],
                    'tiff' => ['image/tiff'],
                    'png' => ['image/png'],
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
                Action\Info::class => Factory\InfoFactory::class,
            ],
        ];
    }
}
