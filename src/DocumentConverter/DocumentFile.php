<?php namespace DocumentService\DocumentConverter;

use Exception;

class DocumentFile extends File{
    function convertToPdf(): PdfFile{
        $dir = new Directory();
        $ooDir = new Directory();

        $cmd = (Config::getInstance())->get('ooBinary').' -env:SingleAppInstance=false -env:UserInstallation=file:///'.$ooDir->getPath().' --convert-to pdf ' . $this->_path . ' --outdir ' . $dir->getPath(). ' --headless --norestore';
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            throw new Exception('soffice operation failed', 50131);
        }

        return $dir->getFiles(PdfFile::class)[0];
    }
}