MW_IMAGE=registry.metaways.net/tine/document-preview-service
DOCKERHUB_IMAGE=tinegroupware/document-preview-service
MAJOR_MINIOR_VERSION=$(shell cat composer.json | jq -r '.version')
FULL_VERSION=${MAJOR_MINIOR_VERSION}.${shell git rev-parse --short HEAD}
FULL_VERSION_IMAGE_NAME=${MW_IMAGE}:${FULL_VERSION}

docker: Dockerfile $(shell find ./etc) $(shell find ./config) $(shell find ./public) $(shell find ./src) $(shell find ./bin)
	rm -rf vendor || true
	composer install --no-dev --ignore-platform-reqs
	sed -i "s/VERSION_T/${FULL_VERSION}/g" Dockerfile
	docker build -t ${FULL_VERSION_IMAGE_NAME}  .
	sed -i "s/${FULL_VERSION}/VERSION_T/g" Dockerfile

dockerReleaseMw: docker
	docker tag ${FULL_VERSION_IMAGE_NAME} ${MW_IMAGE}:${MAJOR_MINIOR_VERSION}
	docker push ${FULL_VERSION_IMAGE_NAME} 
	docker push ${MW_IMAGE}:${MAJOR_MINIOR_VERSION}

dockerReleaseDockerhub: docker
	docker tag ${FULL_VERSION_IMAGE_NAME} ${DOCKERHUB_IMAGE}:${MAJOR_MINIOR_VERSION}
	docker tag ${FULL_VERSION_IMAGE_NAME} ${DOCKERHUB_IMAGE}:${FULL_VERSION}
	docker push ${DOCKERHUB_IMAGE}:${FULL_VERSION}
	docker push ${DOCKERHUB_IMAGE}:${MAJOR_MINIOR_VERSION}

dockerRelease: dockerReleaseMw dockerReleaseDockerhub