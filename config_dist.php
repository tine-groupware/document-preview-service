<?php
return array(
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
        'txt', 'rtf', 'odt', 'ods', 'odp', 'doc', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx', 'pdf', 'jpg', 'jpeg', 'gif', 'tiff', 'png'
    ),
    "docExt" => array(
        'txt', 'rtf', 'odt', 'ods', 'odp', 'doc', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx'
    ),
    "imgExt" => array(
        'jpg', 'jpeg', 'gif', 'tiff', 'png'
        //RAW requires UFraw
    )
);