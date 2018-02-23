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

cd build/var/www/documentPreviewService && composer install
cd build/var/www/documentPreviewService && composer development-disable
cd build/var/www/documentPreviewService && rm composer.json
cd build/var/www/documentPreviewService && rm composer.lock

mkdir build/etc
mkdir build/etc/documentPreviewService
cp sample_config.php build/etc/documentPreviewService/config.php

mkdir build/etc/apache2
mkdir build/etc/apache2/sites-available
mkdir build/etc/apache2/keys
cp sample_apache2.conf build/etc/apache2/sites-available/documentPreviewService.conf

mkdir build/var/log
mkdir build/var/log/documentPreviewService
touch build/var/log/documentPreviewService/auth.log
touch build/var/log/documentPreviewService/doc.log

echo "$CI_COMMIT_REF_NAME - $CI_PIPELINE_ID - $CI_PROJECT_URL" > build/var/www/documentPreviewService/buildnumber

mkdir build/DEBIAN
cp packageinfo build/DEBIAN/control

tar -zcf documentPreview-$CI_COMMIT_REF_NAME.tar.gz build/var/www/documentPreviewService

mkdir build/var/www/documentPreviewService/public/download

dpkg -b ./build documentPreviewService.deb