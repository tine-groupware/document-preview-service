<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter\Converter;


use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\Converter;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentConverter\Request;
use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use Zend\Log\Logger;

class ImageToImage implements Converter
{

    public function from(): array
    {
        return ['jpg', 'jpeg', 'gif', 'tiff', 'png', 'pdf.png'];
    }

    protected $defaultTo = 'png';

    public function to(): array
    {
        return ['jpg', 'jpeg', 'gif', 'tiff', 'png'];
    }

    public function routeTo(): array
    {
        return [];
    }

    /**
     * @param File $file
     * @param Request $request
     * @return File[]
     * @throws DocumentPreviewException
     * @throws \DocumentService\ExtensionDoseNotMatchMineTypeException
     */
    public function convert(File $file, Request $request): array
    {
        $path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.$request->fileType;

        $cmd = 'gm convert ' . $file->getPath() . ' -resize ' . escapeshellarg($request->x . 'x' . $request->y);
        if (false != $request->color) {
            $cmd .= ' -gravity center -background ' . escapeshellarg($request->color) . ' -extent '
                . escapeshellarg($request->x . 'x' . $request->y);
        }
        $cmd .= ' ' . escapeshellarg($path);
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);

        foreach ($rtn as $line) {
            (ErrorHandler::getInstance())->log(0 == $err ? Logger::DEBUG : Logger::INFO, $line, __METHOD__);
        }

        if (0 !== $err) {
            throw new DocumentPreviewException("graphicsmagick operation failed! output: \n" .  join("\n", $rtn), 801, 500);
        }

        return [new File($path, true, $request->fileType)];
    }
}
