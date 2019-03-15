<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter\Converter;


use DocumentService\DocumentConverter\Converter;
use DocumentService\DocumentConverter\FileSystem\Directory;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentConverter\Request;
use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use Zend\Log\Logger;

class PdfToImage implements Converter
{

    public function from(): array
    {
        return ['pdf', 'gs'];
    }

    protected $defaultTo = 'pdf.png';

    public function to(): array
    {
        return ['pdf.png'];
    }

    public function routeTo(): array
    {
        return ['jpg', 'jpeg', 'gif', 'tiff', 'png'];
    }

    public function convert(File $file, Request $request): array
    {
        $dir = new Directory();
        $cmd = 'gs -q -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT'.
            '=2 "-sDEVICE=pngalpha" -dTextAlphaBits=4 -dGraphicsAlphaBits=4 "-r150x150" -sOutputFile='
            . escapeshellarg($dir->getPath() . 'image%03d.png') . ' '. escapeshellarg($file->getPath())
            . ' -c quit  2>&1';
        $err = 0;
        exec($cmd, $rtn, $err);

        foreach ($rtn as $line) {
            (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $line, __METHOD__);
        }

        if (0 !== $err) {
            throw new DocumentPreviewException('Ghostscript operation failed', 901, 500);
        }

        if (true === $request->firstPage) {
            return $dir->getFiles($this->defaultTo)[0];
        }

        return $dir->getFiles($this->defaultTo);
    }
}
