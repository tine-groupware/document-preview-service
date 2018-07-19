<?php
return [
    // configure for documentPreview
    'documentService' => [
        "tempDir" => "/var/lib/documentPreviewService/", //temp folder, must be rw
        "maxProc" => 4, //maximum concurrent conversions
        "loggerOut" => "/var/log/documentPreviewService/doc.log", // log file documentPreview, can be a file path (or a zend logger)
        "ooBinary"=>'soffice'
    ],
    'authLogger' => '/var/log/documentPreviewService/auth.log', // auth logger, can be a file path (or a zend logger)
    'sentryio' => 'https://<key>:<secret>@sentry.io/<project>', //sentry uri
    
    
    'auth' => [
        [
            'name' => 'documentPreviewService', // used for identification
            'required' => false, // turn auth on or of
            'permission' => '(1=1)'
        ]
    ]
];