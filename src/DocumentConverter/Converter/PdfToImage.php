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
    use ExecTrait;

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

    /**
     * @param File $file
     * @param Request $request
     * @return File[]
     * @throws DocumentPreviewException
     */
    public function convert(File $file, Request $request): array
    {
        $dir = new Directory();
        $cmd = 'gs -q -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT'.
            '=2 "-sDEVICE=pngalpha" -dTextAlphaBits=4 -dGraphicsAlphaBits=4 "-r300x300" -sOutputFile='
            . escapeshellarg($dir->getPath() . 'image%03d.png') . ' '. escapeshellarg($file->getPath())
            . ' -c quit  2>&1';
        $err = 0;
        $rtn = '';
        $this->exec($cmd, $rtn, $err);

        (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $rtn, __METHOD__);

        if (false !== strpos($rtn, "file requires a password")) {
            throw new DocumentPreviewException('Pdf requires a password', 902, 422);
        }
        if (false !== strpos($rtn, "No pages will be processed")) {
            throw new DocumentPreviewException('Pdf is corrupted', 903, 422);
        }

        if (0 !== $err) {
            (ErrorHandler::getInstance())->dlog([
                'message' => 'Ghostscript operation failed',
                'err' => $err,
                'rtn' => $rtn,
                'hash' => $file->getMd5Hash(),
                'file' => $file->getBase64(),
            ], __METHOD__);

            throw new DocumentPreviewException("Ghostscript operation failed! output: \n" .  $rtn, 901, 500);
        }

        if (true === $request->firstPage) {
            return $dir->getFiles($this->defaultTo)[0];
        }

        return $dir->getFiles($this->defaultTo);
    }
}
