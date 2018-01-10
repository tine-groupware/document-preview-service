I: rm -r build
mkdir build

cp -r config build/
cp -r public build/
cp -r src build/
cp composer.json build/
cp README.md build/

cd build && mkdir data
cd build/data && mkdir cache

cd build && composer install
cd build && composer development-disable
cd build && rm composer.json
cd build && rm composer.lock

ls

tar -zcf documentPreview.tar.gz build