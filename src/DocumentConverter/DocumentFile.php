<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use Zend\Log\Logger;

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

        $cmd = (Config::getInstance())->get('ooBinary').' -env:SingleAppInstance=false -env:UserInstallation=file:///'
            .$ooDir->getPath().' --convert-to pdf ' . $this->path . ' --outdir ' . $dir->getPath()
            . ' --headless --norestore 2>&1';
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);

        foreach ($rtn as $line) {
            (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $line, __METHOD__);
        }

        if (0 !== $err) {
            copy(
                $this->path,
                (Config::getInstance())->get('tempdir') . 'error-file' .
                    (ErrorHandler::getInstance())->getUid() . '.' .  pathinfo($this)['extension']
            );
            throw new DocumentPreviewException('soffice operation failed', 601, 500);
        }

        return $dir->getFiles(PdfFile::class)[0];
    }
}