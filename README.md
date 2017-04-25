HowTo install:

apt-get install graphicsmagick apache2 libapache2-mod-php7.0 composer php7.0-xml php7.0-cli php7.0-mbstring ghostscript unzip git libreoffice 

(soffice needs to be able to create its config directories in the users home/.config; www-data has /var/www/ as home directory)
mkdir /var/www/.config
chown www-data:www-data /var/www/.config

cd /var/www
git clone git@gitlab.metaways.net:tine20/documentPreview.git ./documentPreviewService

cd /var/www/documentPreviewService
mkdir ./temp
chown www-data:www-data ./temp
mkdir ./public/download
chown www-data:www-data ./public/download