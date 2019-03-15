<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter\Converter;


use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\Converter;
use DocumentService\DocumentConverter\FileSystem\Directory;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentConverter\Request;
use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use Zend\Log\Logger;

class XlsxToOds implements Converter
{

    public function from(): array
    {
        return ['xlsx'];
    }

    protected $defaultTo = 'ods';

    public function to(): array
    {
        return ['ods'];
    }

    public function routeTo(): array
    {
        return ['jpg', 'jpeg', 'gif', 'tiff', 'png', 'pdf', 'gs'];
    }

    public function convert(File $file, Request $request): array
    {
        $dir = new Directory();
        $ooDir = new Directory();

        $cmd = (Config::getInstance())->get('ooBinary').' -env:SingleAppInstance=false -env:UserInstallation=file:///'
            .$ooDir->getPath().' --convert-to ods ' . $file->getPath() . ' --outdir ' . $dir->getPath()
            . ' --headless --norestore 2>&1';
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);

        foreach ($rtn as $line) {
            (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $line, __METHOD__);
        }

        if (0 !== $err) {
            copy(
                $file->getPath(),
                (Config::getInstance())->get('tempdir') . 'error-file' .
                (ErrorHandler::getInstance())->getUid() . '.' .  pathinfo($this)['extension']
            );
            throw new DocumentPreviewException('soffice operation failed', 601, 500);
        }

        return [$dir->getFiles($this->defaultTo)[0]];
    }
}