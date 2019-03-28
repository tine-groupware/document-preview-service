<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentPreviewException;

class PipelineConverter
{
    /**
     * @var Converter[]
     */
    protected $converters;

    /**
     * @var Finalizer[]
     */
    protected $finalizers;

    public function __construct($converters, $finalizers)
    {
        $this->converters = $converters;
        $this->finalizers = $finalizers;
    }

    public function convert(array $files, Request $request): array
    {
        $convertedFiles = [];

        foreach ($request->fileTypes as $fileType) {
            $request->fileType = $fileType;

            $convertedFiles = [];

            foreach ($files as $file) {
                $convertedFiles = array_merge($convertedFiles, $this->convertFile($file, $request));
            }
            $files = $convertedFiles;
        }

        foreach ($this->getFinalizers($request->fileType) as $finalizer) {
            $convertedFiles = $finalizer->convert($convertedFiles, $request);
        }

        return $convertedFiles;
    }

    protected function convertFile(File $file, Request $request): array
    {
        if ($file->getFormat() === $request->fileType) {
            return [$file];
        }

        $converter = $this->getNextConverter($file->getFormat(), $request->fileType);
        if ($converter === null) {
            throw new DocumentPreviewException("Conversion not possible", 0, 400);
        }

        $newFiles =  $converter->convert($file, $request);

        $convertedFiles = [];
        foreach ($newFiles as $newFile) {
            $files = $this->convertFile($newFile, $request);
            $convertedFiles = array_merge($convertedFiles, $files);
        }

        return $convertedFiles;
    }

    protected function getNextConverter(string $currentFormat, string $toFormat): Converter
    {
        $nextConverter = null;
        foreach ($this->converters as $converter) {
            if (in_array($currentFormat, $converter->from())) {
                if (in_array($toFormat, $converter->to())) {
                    return $converter;
                }
                if (null === $nextConverter && in_array($toFormat, $converter->routeTo())) {
                    $nextConverter = $converter;
                }
            }
        }
        return $nextConverter;
    }

    protected function getFinalizers(string $format): array
    {
        $finalizers = [];
        foreach ($this->finalizers as $finalizer) {
            if (in_array($format, $finalizer->format())) {
                $finalizers []= $finalizer;
            }
        }
        return $finalizers;
    }
}
