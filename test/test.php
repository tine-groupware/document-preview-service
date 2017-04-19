<?php

require 'publicDocumentPreview.php';
class testing
{
    function test()
    {
        $docPre = new docPre('');
        echo $docPre->_checkExtension("/test/imAFile.oc", ["doc", "txt", "odt"]);
    }
}
$foo = new testing();
$foo->test();