res=$(curl -i -F config="{\"test\": {\"firstPage\":true,\"filetype\":\"$3\",\"x\":100,\"y\":100,\"color\":false}}" -F "file=@$2" $1/v2/documentPreviewService)
echo ${res:309:-2} | base64 --decode -i
