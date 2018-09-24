curl -i -F config='{"test": {"firstPage":true,"filetype":"jpg","x":100,"y":100,"color":false}}' -F "file=@./test.odt" $1/v2/documentPreviewService
