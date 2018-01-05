sudo apt-get install graphicsmagick apache2 libapache2-mod-php7.0 composer php7.0-xml php7.0-cli php7.0-mbstring ghostscript unzip git libreoffice

cp -r test build/
cp phpunit.xml build
cd build && phpunit