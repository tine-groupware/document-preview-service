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

class DocumentToPdf implements Converter
{

    use ExecTrait;

    public function from(): array
    {
        return [
            'txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx',
            'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx',
            ];
    }

    protected $defaultTo = 'pdf';

    public function to(): array
    {
        return ['pdf'];
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
        $ooDir = new Directory();
        $tmpDir = new Directory();

        //TMPDIR sets soffice tempdir. so lu*.tmp files can be deleted
        //-env:UserInstallation=file:///... otherwise only one instance of soffice can run for the current user
        $cmd = (Config::getInstance())->get('locales') . ' TMPDIR=' . $tmpDir->getPath() . ' ' . (Config::getInstance())->get('ooBinary')
            . ' -env:SingleAppInstance=false -env:UserInstallation=file:///' .$ooDir->getPath() . ' --convert-to pdf '
            . $file->getPath() . ' --outdir ' . $dir->getPath() . ' --headless --norestore 2>&1';


        $rtn = '';
        $err = 0;
        $this->exec($cmd, $rtn, $err);

        (ErrorHandler::getInstance())->dlog(['sofficeReturnCode' => $err, 'output' => $rtn], __METHOD__);
        (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $rtn, __METHOD__);

        if (strpos($rtn, "Error: source file could not be loaded") !== false) {
            throw new DocumentPreviewException('corrupted document', 602, 400);
        }

        if (0 !== $err) {
            throw new DocumentPreviewException("soffice operation failed! output: \n" .  $rtn, 601, 500);
        }

        try {
            return [$dir->getFiles($this->defaultTo)[0]];
        } catch (\Exception $exception) {
            (ErrorHandler::getInstance())->dlog(
                [
                    'mime_tyoe' => mime_content_type($file->getPath()),
                    'path' => $file->getPath(),
                    'hash' => $file->getMd5Hash()
                ],
                __METHOD__
            );

            throw $exception;
        }
    }
}
