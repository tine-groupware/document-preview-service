<?php declare(strict_types=1);

namespace DocumentService;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\DocumentFile;
use DocumentService\DocumentConverter\File;
use DocumentService\DocumentConverter\ImageFile;
use DocumentService\DocumentConverter\PdfFile;
use DocumentService\DocumentConverter\Request;

/**
 * Converts multible files to to specs
 * Class DocumentConverter
 *
 * @package DocumentService
 */
class DocumentConverter
{


    /**
     * Converts multible files to to specs
     *
     * @param array $files files to convert
     * @param array $requests  config
     *
     * @return array base64 encoded results
     * @throws DocumentPreviewException
     */
    public function __invoke(array $files, array $requests): array
    {
        $this->checkAllSame($files);

        $requests = Request::newRequests($requests);

        $rtn = [];


        if ($files[0] instanceof DocumentFile) {
            foreach ($requests as $key => $request) {
                $rtn[$key] = File::toBase64Array($this->convertToDoc($files, $request));
            }
        } elseif ($files[0] instanceof PdfFile) {
            foreach ($requests as $key => $request) {
                $rtn[$key] = File::toBase64Array($this->mergePdf($files, $request));
            }
        } elseif ($files[0] instanceof ImageFile) {
            foreach ($requests as $key => $request) {
                $rtn[$key] = File::toBase64Array($this->convertToImage($files, $$request));
            }
        } else {
            throw new DocumentPreviewException('file extension unknown', 201, 415);
        }

        return $rtn;
    }

    /**
     * Conversion functions, convert files and
     * pass them on or break if the specified filetype is reached
     *
     * @param array $files "
     * @param Request $request  "
     *
     * @return array
     * @throws DocumentPreviewException config not initialized
     */
    protected function convertToDoc(array $files, Request $request): array
    {
        if (in_array(mb_strtolower($request->fileType), (Config::getInstance())->get('docExt'))) {
            return $files;
        }
        return $this->convertToPdf($files, $request);
    }

    /**
     * Converts DocumentFiles to PdfFiles
     *
     * @param array $files "
     * @param Request $request  "
     *
     * @return array
     * @throws DocumentPreviewException pdf merge fails
     */
    protected function convertToPdf(array $files, Request $request): array
    {
        $pdfs = [];

        foreach ($files as $file) {
            $pdfs[] = $file->convertToPdf();
        }
        return $this->mergePdf($pdfs, $request);
    }

    /**
     * Merges PdfFiles in array order
     *
     * @param array $files "
     * @param Request $request  "
     *
     * @return array
     * @throws DocumentPreviewException pdf merge fail
     * @throws DocumentPreviewException config not initialized
     */
    protected function mergePdf(array $files, Request $request): array
    {
        if (true === $request->merge && count($files) > 1) {
            $files = [PdfFile::merge($files)];
        }
        if (in_array(mb_strtolower($request->fileType), (Config::getInstance())->get('pdfExt'))) {
            return $files;
        }
        return $this->convertToPng($files, $request);
    }

    /**
     * Converts PDFFiles To Png
     *
     * @param array $files "
     * @param Request $request  "
     *
     * @return array
     */
    protected function convertToPng(array $files, Request $request): array
    {
        $images = [];

        foreach ($files as $file) {
            if (false == $request->firstPage) {
                foreach ($file->convertToPng() as $image) {
                    $images[] = $image;
                }
            } else {
                $images[] = $file->convertToPng()[0];
            }
        }

        return $this->convertToImage($images, $request);
    }


    /**
     * Converts images to images and changes size
     *
     * @param array $files "
     * @param Request $request  "
     *
     * @return array
     */
    protected function convertToImage(array $files, Request $request): array
    {
        $images = [];

        foreach ($files as $file) {
            $images[] = $file->fitToSize($request->fileType, $request->x, $request->y, $request->color);
        }
        return $images;
    }


    /**
     * Checks if all files have the same type
     *
     * @param array $files "
     *
     * @return void
     * @throws BadRequestException file types differ
     */
    protected function checkAllSame(array $files): void
    {
        $class = get_class($files[0]);
        foreach ($files as $file) {
            if (get_class($file) != $class) {
                throw new BadRequestException('file types differ', 202, 400);
            }
        }
    }
}
