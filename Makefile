MAJOR_MINIOR_VERSION=$(shell cat composer.json | jq -r '.version')
FULL_VERSION=${MAJOR_MINIOR_VERSION}.${shell git rev-parse --short HEAD}
MAJOR_MINIOR_VERSION_IMAGE_NAME=tinegroupware/document-preview-service:${MAJOR_MINIOR_VERSION}
FULL_VERSION_IMAGE_NAME=tinegroupware/document-preview-service:${FULL_VERSION}

docker: Dockerfile $(shell find ./etc) $(shell find ./config) $(shell find ./public) $(shell find ./src) $(shell find ./bin)
	rm -rf vendor || true
	composer install --no-dev --ignore-platform-reqs
	sed -i "s/VERSION_T/${FULL_VERSION}/g" Dockerfile
	docker build -t ${FULL_VERSION_IMAGE_NAME}  .
	sed -i "s/${FULL_VERSION}/VERSION_T/g" Dockerfile

dockerRelease: Dockerfile $(shell find ./etc) $(shell find ./config) $(shell find ./public) $(shell find ./src) $(shell find ./bin)
	docker tag ${FULL_VERSION_IMAGE_NAME} ${MAJOR_MINIOR_VERSION_IMAGE_NAME}
	docker push ${FULL_VERSION_IMAGE_NAME} 
	docker push ${MAJOR_MINIOR_VERSION_IMAGE_NAME}
