<?php declare(strict_types=1);

namespace DocumentService;

use DocumentService\DocumentConverter\Converter\DocumentToPdf;
use DocumentService\DocumentConverter\Converter\ImageToImage;
use DocumentService\DocumentConverter\Converter\PdfToImage;
use DocumentService\DocumentConverter\Converter\XlsxToOds;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentConverter\Finalizer\PdfMergeFinalizer;
use DocumentService\DocumentConverter\PipelineConverter;
use DocumentService\DocumentConverter\Request;

/**
 * Converts multiple files to to specs
 * Class DocumentConverter
 *
 * @package DocumentService
 */
class DocumentConverter
{
    /**
     * @var PipelineConverter
     */
    protected $pipelineConverter;

    public function __construct()
    {
        $this->pipelineConverter = new PipelineConverter(
            [
                new DocumentToPdf(),
                new PdfToImage(),
                new ImageToImage(),
                new XlsxToOds(),
            ],
            [
                new PdfMergeFinalizer(),
            ]
        );
    }

    /**
     * Converts multiple files to to specs
     *
     * @param array $files files to convert
     * @param array $requests  config
     *
     * @return array base64 encoded results
     * @throws DocumentPreviewException
     */
    public function __invoke(array $files, array $requests): array
    {
        $requests = Request::newRequests($requests);

        $rtn = [];

        foreach ($requests as $key => $request) {
            $convertedFiles = $this->pipelineConverter->convert($files, $request);
            $rtn[$key] = File::toBase64Array($convertedFiles);
        }

        return $rtn;
    }
}
