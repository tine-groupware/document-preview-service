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
    use ExecTrait;

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

    /**
     * @param File $file
     * @param Request $request
     * @return File[]
     * @throws DocumentPreviewException
     */
    public function convert(File $file, Request $request): array
    {
        $dir = new Directory();
        $ooDir = new Directory();

        $cmd = (Config::getInstance())->get('locales') . ' ' . (Config::getInstance())->get('ooBinary').' -env:SingleAppInstance=false -env:UserInstallation=file:///'
            .$ooDir->getPath().' --convert-to ods ' . $file->getPath() . ' --outdir ' . $dir->getPath()
            . ' --headless --norestore 2>&1';
        $rtn = '';
        $err = 0;
        $this->exec($cmd, $rtn, $err);

        (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $rtn, __METHOD__);

        if (0 !== $err) {
            copy(
                $file->getPath(),
                (Config::getInstance())->get('tempdir') . 'error-file' .
                (ErrorHandler::getInstance())->getUid() . '.' .  pathinfo($this)['extension']
            );
            throw new DocumentPreviewException("soffice operation failed! output: \n" .  $rtn, 601, 500);
        }

        return [$dir->getFiles($this->defaultTo)[0]];
    }
}
