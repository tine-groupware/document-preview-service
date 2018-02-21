nexususer=documentPreview
nexuspass=1cf129712ae98d92f085

curl -v --user "$nexususer:$nexuspass" -H "Content-Type: application/tar+gzip" --upload-file ./documentPreview-$CI_COMMIT_REF_NAME.tar.gz https://nexus.metaways.net/repository/documentPreview/$CI_JOB_ID/documentPreviewService.tar.gz
curl -v --user "$nexususer:$nexuspass" -H "Content-Type: application/x-debian-package" --upload-file ./documentPreviewService.deb https://nexus.metaways.net/repository/documentPreview/$CI_JOB_ID/documentPreviewService.deb
