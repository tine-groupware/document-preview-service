#!/usr/bin/env bash
I: rm -r build
mkdir build
mkdir build/usr
mkdir build/usr/share
mkdir build/usr/share/documentPreviewService

cp -r config build/usr/share/documentPreviewService
cp -r public build/usr/share/documentPreviewService
cp -r src build/usr/share/documentPreviewService
cp composer.json build/usr/share/documentPreviewService
cp README.md build/usr/share/documentPreviewService

mkdir build/usr/share/documentPreviewService/data
mkdir build/usr/share/documentPreviewService/data/cache

cd build/usr/share/documentPreviewService && php ../../../../composer.phar install --no-dev
cd build/usr/share/documentPreviewService && rm composer.json
cd build/usr/share/documentPreviewService && rm composer.lock

mkdir build/etc
mkdir build/etc/documentPreviewService
mkdir build/etc/documentPreviewService/__VERSION_N_MM__
cp sample_config.php build/etc/documentPreviewService/__VERSION_N_MM__/config.php.sample

mkdir build/var
mkdir build/var/log
mkdir build/var/log/documentPreviewService/

mkdir build/DEBIAN

echo "__VERSION_T__ - __PROJECT_URL" > build/usr/share/documentPreviewService/buildnumber

sed -i "s/VERSION/__VERSION_N_MM__/g" build/usr/share/documentPreviewService/config/config.php

sed "s/VERSION/__VERSION_N_MM__/g" sample_config.php > build/etc/documentPreviewService/__VERSION_N_MM__/config.php

sed "s/VERSION/__VERSION_N_MMP__/g" packageinfo > build/DEBIAN/control

sed "s/VERSION/__VERSION_N_MM__/g" postinst.sh > build/DEBIAN/postinst

chmod 775 build/DEBIAN/postinst

tar -zcf documentPreview-__VERSION_T__.tar.gz build/usr/share/documentPreviewService/

dpkg -b ./build documentPreviewService-__VERSION_T__.deb