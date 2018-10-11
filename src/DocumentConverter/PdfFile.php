<?php namespace DocumentService\DocumentConverter;

use Exception;

/**
 * Repesents a Pdf file
 * Deletes file on destruction
 * @package DocumentService\DocumentConverter
 */
class PdfFile extends File {

    /**
     * converts pdf to pngs using ghostscript
     * @return array image files
     * @throws Exception
     */
    function convertToPng(): array {
        $dir = new Directory();
        $cmd = 'gs -q -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT=2 "-sDEVICE=pngalpha" -dTextAlphaBits=4 -dGraphicsAlphaBits=4 "-r150x150" -sOutputFile=' . escapeshellarg($dir->getPath() . 'image%03d.png') . ' ' . escapeshellarg($this->_path) . ' -c quit';
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            throw new Exception('Ghostscript operation failed', 50161);
        }

        return $dir->getFiles(ImageFile::class);
    }

    /**
     * Merges multiple pdf files in order using ghostscript
     * @param array $files PdfFiles
     * @return PdfFile
     * @throws Exception
     */
    static function merge(array $files): PdfFile {
        $path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.'pdf';
        $cmd = ('gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile='.$path);
        foreach ($files as $file)
            $cmd .= ' '.$file->getPath();
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            throw new Exception('Ghostscript operation failed', 50162);
        }

        return new PdfFile($path, true);
    }
}