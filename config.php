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
        "odt",
        "ods"
    ),
    "docExt" => array(
        "doc",
        "odt",
        "ods",
    ),
    "imgExt" => array(
        "jpg",
        "png",
        "gif",
        "jpeg",
        //RAW requires UFraw
    )
);