<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentPreviewException;

/**
 * Repesents a Document file
 * Deletes file on destruction
 *
 * @package DocumentService\DocumentConverter
 */
class DocumentFile extends File
{
    /**
     * Converts Documentfiles to pdfs using soffice
     *
     * @return PdfFile
     * @throws DocumentPreviewException config not initialized
     * @throws DocumentPreviewException soffice operation failed
     */
    public function convertToPdf(): PdfFile
    {
        $dir = new Directory();
        $ooDir = new Directory();

        $cmd = (Config::getInstance())->get('ooBinary').' -env:SingleAppInstance=false -env:UserInstallation=file:///'.$ooDir->getPath().' --convert-to pdf ' . $this->_path . ' --outdir ' . $dir->getPath(). ' --headless --norestore 2> '.(Config::getInstance())->get('stderr');
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err) {
            throw new DocumentPreviewException('soffice operation failed', 601, 500);
        }

        return $dir->getFiles(PdfFile::class)[0];
    }
}