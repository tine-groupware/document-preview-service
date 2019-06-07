### Install ubuntu (bionic)

add repos:

    add-apt-repository 'deb [trusted=yes] http://apt.metaways.net/private/documentservice/ bionic main'
    add-apt-repository ppa:libreoffice/libreoffice-6-1

install:

    apt-get update
    apt-get install documentPreviewService
    
### config
    
/etc/documentPreviewService/2.1/config.php

``` php
<?php
return [
    'documentService' => [
        "tempDir" => "/var/lib/documentPreviewService/", //temp folder, must be rw
        "maxProc" => 4, //maximum concurrent conversions
        "loggerOut" => "/var/log/documentPreviewService/doc.log", // log file path, must exist and be writable 
	"ooBinary"=>'soffice',
	"logLevel" => "7", // Syslog Severity Level
    ],
    'authLogger' => '/var/log/documentPreviewService/auth.log', // auth logger path, must exist and be writable 
    'sentryio' => '', //sentry uri
    
    
    'auth' => [
        [
            'name' => 'documentPreviewService',
            'required' => false,
            'permission' => '(1=1)'
        ]
    ]
];
```

webserver configuration:

    root: /usr/share/documentPreviewService/public/
    AllowOverride All
    
    for ssl auth:
    SSLVerifyClient optional
    SSLOptions +ExportCertData
    SSLOptions +StdEnvVars
