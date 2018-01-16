<?php
return array(
    // open office binary
    'ooBinary' => 'soffice',
    // Directory to store uploaded files temporarily.
    "tempDir" => "../temp/",
    // Directory to store finished files
    "downDir" => "download/",
    // URL from downDir
    "downUrl" => "http://nsa.cloud/",
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
