# Document Preview Service
## Build
#### Docker
1. install composer locally
2. make docker, to build the docker image
3. make dockerRelease, to push the images to docker hub

## Installation
#### Docker
1. Install docker
2. Start Document Preview Service as docker container
```shell script
docker run --restart=always -p 80:80 -d --name document-preview-service tinegroupware/document-preview-service:<version>

# with config
docker run --restart=always -p 80:80 -d --name document-preview-service -v /path/to/config:/etc/documentPreviewService/VERSION/config.php tinegroupware/document-preview-service:<version>

# listening only locally on 8080
# this can then could be forwarded by an nginx reverse proxy with ssl 
docker run --restart=always -p 127.0.0.1:8080:80 -d --name document-preview-service tinegroupware/document-preview-service:<version>

# with docker-compose
# copy docker-compose file
docker-compose up
```
