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
        "jpg",
        "png",
        "gif",
        "jpeg",
        "doc",
        "docx",
        "xls",
        "xlsx",
        "odt",
        "ods",
        "pdf"
    ),
    "docExt" => array(
        "doc",
        "docx",
        "xls",
        "xlsx",
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