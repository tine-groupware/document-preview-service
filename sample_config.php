<?php
class ConfigProvider
{
    public function __invoke()
    {
        return [
            // configure for documentPreview
            'documentService' => [
                "tempDir" => "/tmp", //temp folder
                "downDir" => "/var/www/documentPreviewService/public/download", //download dir for converted files, should be cleaned regularly
                "downUrl" => "https://127.0.0.1/download", //url for download dir
                "maxProc" => 4, //maximum concurrent conversions
                "loggerOut" => "/var/log/documentPreviewService/doc.log", // log file documentPreview, can be a file or a zend logger
                //list of allowed extensions
                "ext" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx', 'pdf', 'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
                //list of libreoffice extensions
                "docExt" => [
                    'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx',
                ],
                // list of graphicsmagick extensions
                "imgExt" => [
                    'jpg', 'jpeg', 'gif', 'tiff', 'png'
                ],
                // libre office binary
                "ooBinary"=>'soffice'
            ],

            // routing and authentication setup
            // for authentication documentation see tine20/auth-middleware
            'routes' => [
                // list of routes
                [
                    'name' => 'documentPreview', // used for identification
                    'path' => '/tine20/documentPreview', // uri prefix for route
                    // sequential list of middelware
                    'middleware' => [
                        Auth\Action\NeedsAuth::class, // auth injector
                        Auth\Action\AuthSSL::class, // ssl auth
                        Auth\Action\AuthCheck::class, // auth check
                        DocumentService\Action\DocumentPreview::class, // DocumentPreview middelware
                    ],
                    'allowed_methods' => ['POST'],
                    // auth settings for this route
                    'auth' =>[
                        'required' => true,
                        'permission' => '(1=1)'
                    ]
                ],
            ],
            // auth settings
            'auth' => [
                'default' => ['name' => 'default', 'permission' => "false",] // default authentication, if no auth is configured auth will fail
            ],
            'authLogger' => '/var/log/documentPreviewService/auth.log', // auth logger, can be a file or a zend logger
        ];
    }
}