<?php
return [
    // configure for documentPreview
    'documentService' => [
        "maxProc" => 4, //maximum concurrent conversions
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
        // preview service
        [
            'name' => 'documentPreviewService', // used for identification
            'path' => '/v2/documentPreviewService', // uri prefix for route
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
        ], [ // Ping api with check Authorisation
            'name' => 'apiPingAuth', // used for identification
            'path' => '/v2/ping/auth', // uri prefix for route
            // sequential list of middelware
            'middleware' => [
                Auth\Action\NeedsAuth::class, // auth injector
                Auth\Action\AuthSSL::class, // ssl auth
                Auth\Action\AuthCheck::class, // auth check
                DocumentService\Action\ApiPing::class // ping class
            ],
            'allowed_methods' => ['POST'],
            // auth settings for this route
            'auth' =>[
                'required' => true,
                'permission' => '(1=1)'
            ]
        ],[ // Ping api without
            'name' => 'apiPing', // used for identification
            'path' => '/v2/ping', // uri prefix for route
            // sequential list of middelware
            'middleware' => [
                DocumentService\Action\ApiPing::class // ping class
            ],
            'allowed_methods' => ['POST','GET'],
        ]
    ],
    // auth settings
    'auth' => [
        'default' => ['name' => 'default', 'permission' => "false",] // default authentication, if no auth is configured auth will fail
    ],
];