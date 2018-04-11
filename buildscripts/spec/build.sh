#!/usr/bin/env bash
I: rm -r build
mkdir build
mkdir build/var
mkdir build/var/www
mkdir build/var/www/documentPreviewService

cp -r config build/var/www/documentPreviewService
cp -r public build/var/www/documentPreviewService
cp -r src build/var/www/documentPreviewService
cp composer.json build/var/www/documentPreviewService
cp README.md build/var/www/documentPreviewService

mkdir build/var/www/documentPreviewService/data
mkdir build/var/www/documentPreviewService/data/cache

cd build/var/www/documentPreviewService && php ../../../../composer install --no-dev
cd build/var/www/documentPreviewService && rm composer.json
cd build/var/www/documentPreviewService && rm composer.lock

mkdir build/etc
mkdir build/etc/documentPreviewService
mkdir build/etc/documentPreviewService/__VERSION_N_MM__
cp sample_config.php build/etc/documentPreviewService/__VERSION_N_MM__/config.php

mkdir build/var/log
mkdir build/var/log/documentPreviewService/

mkdir build/DEBIAN

echo "__VERSION_T__ - __PROJECT_URL" > build/var/www/documentPreviewService/buildnumber

sed -i "s/VERSION/__VERSION_N_MM__/g" build/var/www/documentPreviewService/config/config.php

sed "s/VERSION/__VERSION_N_MM__/g" sample_config.php > build/etc/documentPreviewService/__VERSION_N_MM__/config.php

sed "s/VERSION/__VERSION_N_MMP__/g" packageinfo > build/DEBIAN/control

sed "s/VERSION/__VERSION_N_MM__/g" postinst.sh > build/DEBIAN/postinst

chmod 775 build/DEBIAN/postinst

tar -zcf documentPreview-__VERSION_T__.tar.gz build/var/www/documentPreviewService/

dpkg -b ./build documentPreviewService-__VERSION_T__.deb