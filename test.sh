re=$(curl -F config='{"test": {"firstPage":true, "filetype":"pdf"}}' -F "file=@test.odt" http://localhost:5000/v2/documentPreviewService); echo $re | base64 --decode -i > test.pdf

