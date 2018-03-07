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
mkdir build/etc/documentPreviewService/$CI_PIPELINE_ID
cp sample_config.php build/etc/documentPreviewService/$CI_PIPELINE_ID/config.php

mkdir build/var/log
mkdir build/var/log/documentPreviewService/

mkdir build/DEBIAN

echo "$CI_COMMIT_REF_NAME - $CI_PIPELINE_ID - $CI_PROJECT_URL" > build/var/www/documentPreviewService/buildnumber

sed -i "s/documentpreviewconfig/documentpreviewconfig$CI_PIPELINE_ID/g" build/var/www/documentPreviewService/config/config.php

sed "s/VERSION/$CI_PIPELINE_ID/g" sample_config.php > build/etc/documentPreviewService/$CI_PIPELINE_ID/config.php

sed "s/VERSION/$CI_PIPELINE_ID/g" packageinfo > build/DEBIAN/control

sed "s/VERSION/$CI_PIPELINE_ID/g" postinst.sh > build/DEBIAN/postinst

chmod 775 build/DEBIAN/postinst

tar -zcf documentPreview-$CI_COMMIT_REF_NAME.tar.gz build/var/www/documentPreviewService/

mkdir build/var/www/documentPreviewService/public/download

dpkg -b ./build documentPreviewService$CI_PIPELINE_ID.deb