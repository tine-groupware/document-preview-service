#!/usr/bin/env bash
#curl -v --user "documentPreview:1cf129712ae98d92f085" -H "Content-Type: application/tar+gzip" --upload-file ./documentPreview-__VERSION_T__.tar.gz https://nexus.metaways.net/repository/documentPreview/__REF_NAME__/__VERSION_PATH__/documentPreviewService.tar.gz
#curl -v --user "documentPreview:1cf129712ae98d92f085" -H "Content-Type: application/x-debian-package" --upload-file ./documentPreviewService-__VERSION_T__.deb https://nexus.metaways.net/repository/documentPreview/__REF_NAME__/__VERSION_PATH__/documentPreviewService.deb
#curl -v --user "documentPreview:1cf129712ae98d92f085" -H "Content-Type: application/x-debian-package" --upload-file ./documentPreviewService-__VERSION_T__.deb https://nexus.metaways.net/repository/documentPreview/__REF_NAME__/documentPreviewService.deb

scp ./documentPreviewService-__VERSION_T__.deb vaptrepo01.lv3.metaways.net:/data/incoming/private/documentservice/xenial/