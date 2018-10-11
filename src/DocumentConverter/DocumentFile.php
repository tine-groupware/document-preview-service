<?php namespace DocumentService\DocumentConverter;

use Exception;

/**
 * Repesents a Document file
 * Deletes file on destruction
 * @package DocumentService\DocumentConverter
 */
class DocumentFile extends File{
    /**
     * Converts Documentfiles to pdfs using soffice
     * @return PdfFile
     * @throws Exception
     */
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