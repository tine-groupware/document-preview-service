#!/usr/bin/env bash
cp composer.json build/usr/share/documentPreviewService/
rm -r build/usr/share/documentPreviewService/vendor
#todo dependecy diff betwean test an deloy/build version
cd build/usr/share/documentPreviewService && php ../../../../composer.phar install
cp -r test build/usr/share/documentPreviewService/
cp phpunit.xml build/usr/share/documentPreviewService/
cd build/usr/share/documentPreviewService && vendor/phpunit/phpunit/phpunit