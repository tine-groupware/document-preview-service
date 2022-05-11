# Document Preview Service
## Build
#### Docker
1. install composer locally
2. configure access to gitlab.metaways.net
3. make docker, to build the docker image
4. make dockerRelease, to push the images to docker hub

## Installation
#### Docker
1. Install docker
2. Start Document Preview Service as docker container
```shell script
docker run --restart=always -p 80:80 -d --name document-preview-service registry.metaways.net/tine/document-preview-service:<version>

# with config
docker run --restart=always -p 80:80 -d --name document-preview-service -v /path/to/config:/etc/documentPreviewService/VERSION/config.php registry.metaways.net/tine/document-preview-service:<version>

# listening only locally on 8080
# this can then could be forwarded by an nginx reverse proxy with ssl 
docker run --restart=always -p 127.0.0.1:8080:80 -d --name document-preview-service registry.metaways.net/tine/document-preview-service:<version>

# with docker-compose
# copy docker-compose file
docker-compose up
```

#### Packet Ubuntu (focal)
1. Document Preview und Libre Office Repos hinzufügen
```shell script
wget -q -O- https://apt.metaways.net/private/documentservice/pubkey.gpg | apt-key add -

add-apt-repository 'deb http://apt.metaways.net/private/documentservice/ focal main'
add-apt-repository ppa:libreoffice/libreoffice-6-4
add-apt-repository ppa:ondrej/php
```
2. Packet installieren
```shell script
apt-get update
apt-get install documentPreviewService
```

#### Packet Ubuntu (bionic)
1. Document Preview und Libre Office Repos hinzufügen
```shell script
wget -q -O- https://apt.metaways.net/private/documentservice/pubkey.gpg | apt-key add -

add-apt-repository 'deb http://apt.metaways.net/private/documentservice/ bionic main'
add-apt-repository ppa:libreoffice/libreoffice-6-4
```
2. Packet installieren
```shell script
apt-get update
apt-get install documentPreviewService
```
#### Packet Debian (stretch)
TODO: update to libreoffice 6.4!
1. Php 7.2, Document Preview und Libre Office 6.1 Repos hinzufügen
```shell script
apt-get update
apt-get install -y apt-transport-https gnupg wget

wget -q -O- https://packages.sury.org/php/apt.gpg | apt-key add -
wget -q -O- https://apt.metaways.net/private/documentservice/pubkey.gpg | apt-key add 

echo "deb https://packages.sury.org/php/ stretch main" | tee /etc/apt/sources.list.d/php.list
echo "deb https://apt.metaways.net/private/documentservice/ bionic main" | tee /etc/apt/sources.list.d/documentservice.list
echo "deb http://deb.debian.org/debian stretch-backports main" | tee /etc/apt/sources.list.d/backports.list
``` 
2. Libre Office und Document Preview Service installieren
```shell script
apt-get update
apt-get -t stretch-backports install libreoffice
apt-get install documentpreviewservice
```
### Apt Repo Access Restriction
Es gibt zwei mögliche Authentifizierungsmethoden IP-Whitelist und http basic auth
#### http basic auth
Der Nutzername und Password müssen in den urls eingetragen werden. Sie werden dann zu:
```shell script
wget -q -O- https://USER:PASS@apt.metaways.net/private/documentservice/pubkey.gpg | apt-key add -
echo "deb https://USER:PASS@apt.metaways.net/private/documentservice/ bionic main" | tee /etc/apt/sources.list.d/documentservice.list
```
## Update
```shell script
apt-get update && apt-get install documentpreviewservice
```
## Configuration
#### Document Preview Config
``` php
cat /etc/documentPreviewService/2.1/config.php
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

#### Webserver Configuration
Für eine einfache Configuration muss der webroot auf das Document Preview
Verzeichniss gesetzt werden. Der Webserver muss außerdem Url Override Erlauben.
```
root: /usr/share/documentPreviewService/public/
AllowOverride All
``` 

Falls SSL Auth verwendet werden soll, müssen noch einige SSL options gesetzt werden.
```
SSLVerifyClient optional
SSLOptions +ExportCertData
SSLOptions +StdEnvVars
```

Um auch die konvertierung von großen Datein zu ermöglichen, sollten noch einen
Maxima erhöht werden.
```
post_max_size=20M
upload_max_filesize=20M
max_execution_time=600
```
