FROM ubuntu:20.04 as php

RUN apt update \
    && apt install -y  software-properties-common dirmngr apt-transport-https lsb-release ca-certificates curl gnupg \
    && add-apt-repository ppa:ondrej/php \
    && add-apt-repository ppa:libreoffice/ppa  \
    && apt update

RUN apt install -y php7.2-xml php7.2-cli php7.2-mbstring php7.2-curl
RUN apt install -y graphicsmagick ghostscript unzip libreoffice
RUN apt install -y nginx php7.2-fpm supervisor locales logrotate cron

RUN locale-gen --lang de_DE.UTF-8

RUN mkdir /run/php \
    && mkdir -p /var/log/documentPreviewService/ \
    && mkdir -p /var/lib/documentPreviewService/ \
    && mkdir -p /usr/share/document-preview/ \
    && mkdir -p /var/www/.cache/ \
    && chown www-data:www-data /var/log/documentPreviewService/ \
    && chown www-data:www-data /var/lib/documentPreviewService/ \
    && chown www-data:www-data /var/www/.cache/ \
    && echo '<?php $buildNumber = "VERSION_T";' >> /usr/share/document-preview/buildnumber

COPY etc/supervisord/* /etc/supervisor/conf.d/
COPY etc/nginx/vhost.conf /etc/nginx/sites-enabled/default
COPY etc/nginx/lb/check.php /etc/nginx/lb/check.php
COPY etc/logrotate/* /etc/logrotate.d/
RUN chmod 644 /etc/logrotate.d/*
COPY etc/php/7.2/fpm/conf.d/* etc/php/7.2/fpm/conf.d/
COPY etc/documentPreviewService/config.php /etc/documentPreviewService/VERSION/config.php

COPY bin /usr/share/document-preview/bin
COPY config /usr/share/document-preview/config
COPY public /usr/share/document-preview/public
COPY src /usr/share/document-preview/src
COPY vendor /usr/share/document-preview/vendor

CMD "/usr/bin/supervisord" "-c" "/etc/supervisor/supervisord.conf" "--nodaemon"