#!/usr/bin/env bash
cp composer.json build/var/www/documentPreviewService/
cp composer.lock build/var/www/documentPreviewService/
cp -r test build/var/www/documentPreviewService/
cp phpunit.xml build/var/www/documentPreviewService/
cd build/var/www/documentPreviewService && vendor/phpunit/phpunit/phpunit