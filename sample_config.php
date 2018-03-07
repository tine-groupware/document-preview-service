<?php
return [
    // configure for documentPreview
    'documentService' => [
        "tempDir" => "/tmp", //temp folder
        "downDir" => "/var/www/documentPreviewService/public/download", //download dir for converted files, should be cleaned regularly
        "downUrl" => "https://127.0.0.1/download/", //url for download dir, should end with a /
        "maxProc" => 4, //maximum concurrent conversions
        "loggerOut" => "/var/log/documentPreviewService/doc.log", // log file documentPreview, can be a file or a zend logger
        "ooBinary"=>'soffice'
    ],
    'authLogger' => '/var/log/documentPreviewService/auth.log', // auth logger, can be a file or a zend logger
    'sentryio' => 'https://<key>:<secret>@sentry.io/<project>' //sentry uri
];