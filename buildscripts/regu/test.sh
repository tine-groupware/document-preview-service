#!/usr/bin/env bash
cp composer.json build/var/www/documentPreviewService/
rm -r build/var/www/documentPreviewService/vendor
#todo dependecy diff betwean test an deloy/build version
cd build/var/www/documentPreviewService && composer install
cp -r test build/var/www/documentPreviewService/
cp phpunit.xml build/var/www/documentPreviewService/
cd build/var/www/documentPreviewService && vendor/phpunit/phpunit/phpunit