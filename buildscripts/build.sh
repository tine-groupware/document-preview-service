I: rm -r build
mkdir build
mkdir build/var
mkdir build/var/www
mkdir build/var/www/documentPreviewService

cp -r config build/var/www/documentPreviewService$CI_PIPELINE_ID
cp -r public build/var/www/documentPreviewService$CI_PIPELINE_ID
cp -r src build/var/www/documentPreviewService$CI_PIPELINE_ID
cp composer.json build/var/www/documentPreviewService$CI_PIPELINE_ID
cp README.md build/var/www/documentPreviewService$CI_PIPELINE_ID

mkdir build/var/www/documentPreviewService$CI_PIPELINE_ID/data
mkdir build/var/www/documentPreviewService$CI_PIPELINE_ID/data/cache

cd build/var/www/documentPreviewService$CI_PIPELINE_ID && composer install
cd build/var/www/documentPreviewService$CI_PIPELINE_ID && composer development-disable
cd build/var/www/documentPreviewService$CI_PIPELINE_ID && rm composer.json
cd build/var/www/documentPreviewService$CI_PIPELINE_ID && rm composer.lock

mkdir build/etc
mkdir build/etc/documentPreviewService
mkdir build/etc/ducumentPreviewService/$CI_PIPELINE_ID
cp sample_config.php build/etc/documentPreviewService/$CI_PIPELINE_ID/config.php

mkdir build/etc/apache2
mkdir build/etc/apache2/sites-available
mkdir build/etc/apache2/keys
cp sample_apache2.conf build/etc/apache2/sites-available/documentPreviewService.conf

mkdir build/var/log
mkdir build/var/log/documentPreviewService$CI_PIPELINE_ID

echo "$CI_COMMIT_REF_NAME - $CI_PIPELINE_ID - $CI_PROJECT_URL" > build/var/www/documentPreviewService$CI_PIPELINE_ID/buildnumber

mkdir build/DEBIAN

sed "s/VERSION/$CI_PIPELINE_ID/g" packageinfo > build/DEBIAN/control

sed "s/VERSION/$CI_PIPELINE_ID/g" postinst.sh > build/DEBIAN/postinst

tar -zcf documentPreview-$CI_COMMIT_REF_NAME.tar.gz build/var/www/documentPreviewService$CI_PIPELINE_ID/

mkdir build/var/www/documentPreviewService$CI_PIPELINE_ID/public/download

dpkg -b ./build documentPreviewService$CI_PIPELINE_ID.deb