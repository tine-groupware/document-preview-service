<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter\Finalizer;


use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentConverter\Finalizer;
use DocumentService\DocumentConverter\Request;
use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use Zend\Log\Logger;

class PdfMergeFinalizer implements Finalizer
{

    public function format(): array
    {
        return ['pdf', 'gs'];
    }

    public function convert(array $files, Request $request): array
    {
        if (true === $request->merge) {
            return $this->mergePdfs($files);
        }
        return $files;
    }

    protected function mergePdfs(array $files)
    {
        $path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.'pdf';
        $cmd = ('gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -sOutputFile='.$path);
        foreach ($files as $file) {
            $cmd .= ' '.$file->getPath();
        }
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);

        foreach ($rtn as $line) {
            (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $line, __METHOD__);
        }

        if (0 !== $err) {
            throw new DocumentPreviewException('Ghostscript operation failed', 902, 500);
        }

        return [new File($path, true, 'pdf')];
    }
}
