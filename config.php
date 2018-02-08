<?php
return array(
	'ooBinary' => 'soffice',
    // Directory to store uploaded files temporarily.
    "tempDir" => "/var/www/documentPreviewService/temp/",
    // Directory to store finished files
    "downDir" => "/var/www/documentPreviewService/public/download/",
    // URL from downDir
    "downUrl" => "http://127.0.0.1/download/",
    // Maximum concurrent processes
    "maxProc" => 4,
    // Path to Logfile
    "logFile" => "../log",
    // Allowed extension
"ext" => array(
        'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx', 'pdf', 'jpg', 'jpeg', 'gif', 'tiff', 'png'
    ),
    "docExt" => array(
        'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx',
    ),
    "imgExt" => array(
        'jpg', 'jpeg', 'gif', 'tiff', 'png'
        //RAW requires UFraw
    )
);
