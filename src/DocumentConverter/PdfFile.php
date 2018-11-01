<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentPreviewException;
use Exception;

/**
 * Repesents a Pdf file
 * Deletes file on destruction
 *
 * @package DocumentService\DocumentConverter
 */
class PdfFile extends File
{

    /**
     * Converts pdf to pngs using ghostscript
     *
     * @return array image files
     *
     * @throws Exception Ghostscript operation failed
     */
    function convertToPng(): array
    {
        $dir = new Directory();
        $cmd = 'gs -q -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT=2 "-sDEVICE=pngalpha" -dTextAlphaBits=4 -dGraphicsAlphaBits=4 "-r150x150" -sOutputFile=' . escapeshellarg($dir->getPath() . 'image%03d.png') . ' ' . escapeshellarg($this->_path) . ' -c quit';
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err) {
            throw new DocumentPreviewException('Ghostscript operation failed', 901, 500);
        }

        return $dir->getFiles(ImageFile::class);
    }

    /**
     * Merges multiple pdf files in order using ghostscript
     *
     * @param array $files PdfFiles
     *
     * @return PdfFile
     * @throws DocumentPreviewException Ghostscript operation failed
     */
    static function merge(array $files): PdfFile
    {
        $path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.'pdf';
        $cmd = ('gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile='.$path);
        foreach ($files as $file) {
            $cmd .= ' '.$file->getPath();
        }
        $cmd .= ' 2> '.(Config::getInstance())->get('stderr');
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err) {
            throw new DocumentPreviewException('Ghostscript operation failed', 902, 500);
        }

        return new PdfFile($path, true);
    }
}