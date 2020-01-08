## Install 
#### ubuntu (bionic)

add repos:

    wget -q -O- https://apt.metaways.net/private/documentservice/pubkey.gpg | apt-key add -

    add-apt-repository 'deb http://apt.metaways.net/private/documentservice/ bionic main'
    add-apt-repository ppa:libreoffice/libreoffice-6-1

install:

    apt-get update
    apt-get install documentPreviewService
    
#### debian (stretch)

install tools:

    apt-get update
    apt-get install -y apt-transport-https gnupg wget
    
add php7.2 and documentservice repos and backports for libreoffice6.1:

    wget -q -O- https://packages.sury.org/php/apt.gpg | apt-key add -
    wget -q -O- https://apt.metaways.net/private/documentservice/pubkey.gpg | apt-key add -
    echo "deb https://packages.sury.org/php/ stretch main" | tee /etc/apt/sources.list.d/php.list
    echo "deb https://apt.metaways.net/private/documentservice/ bionic main" | tee /etc/apt/sources.list.d/documentservice.list
    echo "deb http://deb.debian.org/debian stretch-backports main" | tee /etc/apt/sources.list.d/backports.list
    apt-get update
    
install libreoffice and documentservice:

    apt-get -t stretch-backports install libreoffice
    apt-get install documentpreviewservice

### repo access protection

we have an ip-whitelist on the server to define the ip/range which has access.

NOTE: if apt.metaways.net is to be accessed from an unknown ip, username and password have
 to be added to the urls like this:
 
key import:

    wget -q -O- https://USER:PASS@apt.metaways.net/private/documentservice/pubkey.gpg | apt-key add -
  
sources.list:

    echo "deb https://USER:PASS@apt.metaways.net/private/documentservice/ bionic main" | tee /etc/apt/sources.list.d/documentservice.list

## update

    apt-get update && apt-get install install documentpreviewservice

## config
    
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
