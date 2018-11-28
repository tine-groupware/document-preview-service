<?php declare(strict_types=1);

namespace DocumentService;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\DocumentFile;
use DocumentService\DocumentConverter\File;
use DocumentService\DocumentConverter\ImageFile;
use DocumentService\DocumentConverter\PdfFile;
use Exception;

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
     * @param array $conf  config
     *
     * @return array base64 encoded results
     * @throws DocumentPreviewException
     */
    public function __invoke(array $files, array $conf): array
    {
        $this->checkAllSame($files);

        $conf = $this->cleanConf($conf);

        $rtn = [];


        if ($files[0] instanceof DocumentFile) {
            foreach ($conf as $key => $cnf) {
                $rtn[$key] = File::toBase64Array($this->convertToDoc($files, $cnf));
            }
        } elseif ($files[0] instanceof PdfFile) {
            foreach ($conf as $key => $cnf) {
                $rtn[$key] = File::toBase64Array($this->mergePdf($files, $cnf));
            }
        } elseif ($files[0] instanceof ImageFile) {
            foreach ($conf as $key => $cnf) {
                $rtn[$key] = File::toBase64Array($this->convertToImage($files, $cnf));
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
     * @param array $conf  "
     *
     * @return array
     * @throws DocumentPreviewException config not initialized
     */
    protected function convertToDoc(array $files, array $conf): array
    {
        if (in_array(mb_strtolower($conf['fileType']), (Config::getInstance())->get('docExt'))) {
            return $files;
        }
        return $this->convertToPdf($files, $conf);
    }

    /**
     * Converts DocumentFiles to PdfFiles
     *
     * @param array $files "
     * @param array $conf  "
     *
     * @return array
     * @throws DocumentPreviewException pdf merge fails
     */
    protected function convertToPdf(array $files, array $conf): array
    {
        $pdfs = [];

        foreach ($files as $file) {
            $pdfs[] = $file->convertToPdf();
        }
        return $this->mergePdf($pdfs, $conf);
    }

    /**
     * Merges PdfFiles in array order
     *
     * @param array $files "
     * @param array $conf  "
     *
     * @return array
     * @throws DocumentPreviewException pdf merge fail
     * @throws DocumentPreviewException config not initialized
     */
    protected function mergePdf(array $files, array $conf): array
    {
        if (true === $conf['merge'] && count($files) > 1) {
            $files = [PdfFile::merge($files)];
        }
        if (in_array(mb_strtolower($conf['fileType']), (Config::getInstance())->get('pdfExt'))) {
            return $files;
        }
        return $this->convertToPng($files, $conf);
    }

    /**
     * Converts PDFFiles To Png
     *
     * @param array $files "
     * @param array $conf  "
     *
     * @return array
     */
    protected function convertToPng(array $files, array $conf): array
    {
        $images = [];

        foreach ($files as $file) {
            if (false == $conf['firstPage']) {
                foreach ($file->convertToPng() as $image) {
                    $images[] = $image;
                }
            } else {
                $images[] = $file->convertToPng()[0];
            }
        }

        return $this->convertToImage($images, $conf);
    }


    /**
     * Converts images to images and changes size
     *
     * @param array $files "
     * @param array $conf  "
     *
     * @return array
     */
    protected function convertToImage(array $files, array $conf): array
    {
        $images = [];

        foreach ($files as $file) {
            $images[] = $file->fitToSize($conf['fileType'], $conf['x'], $conf['y'], $conf['color']);
        }
        return $images;
    }

    /**
     * Creates a clean config
     *
     * @param array $config "
     *
     * @return array
     *
     * todo find better solution
     */
    protected function cleanConf(array $config): array
    {
        $configuration = [];
        foreach ($config as $key => $conf) {
            $cnf = [
                'firstPage' => false,
                'fileType' => 'png',
                'x' => 200,
                'y' => 200,
                'color' => false,
                'merge' => true,
            ];

            if (array_key_exists('firstPage', $conf)
                && isset($conf['firstPage'])
                && ('true' === $conf['firstPage'] || true === $conf['firstPage'])
            ) {
                $cnf['firstPage'] = true;
            }
            if (array_key_exists('firstpage', $conf)
                && isset($conf['firstpage'])
                && ('true' === $conf['firstpage'] || true === $conf['firstpage'])
            ) {
                $cnf['firstPage'] = true; //compensate inconsistent api desing
            }
            if (array_key_exists('filetype', $conf) && isset($conf['filetype'])) {
                $cnf['fileType'] = mb_strtolower($conf['filetype']);
            }
            if (array_key_exists('fileType', $conf) && isset($conf['fileType'])) {
                $cnf['fileType'] = mb_strtolower($conf['fileType']); //same as above
            }
            if (array_key_exists('x', $conf) && isset($conf['x'])) {
                $cnf['x'] = $conf['x'];
            }
            if (array_key_exists('y', $conf) && isset($conf['y'])) {
                $cnf['y'] = $conf['y'];
            }
            if (array_key_exists('color', $conf)
                && isset($conf['color'])
                && !('false' === $conf['color'] || false === $conf['color'])
            ) {
                $cnf['color'] = mb_strtolower($conf['color']);
            }
            if (array_key_exists('merge', $conf)
                && isset($conf['merge'])
                && ('false' === $conf['merge'] || false === $conf['merge'])
            ) {
                $cnf['merge'] = false;
            }

            $configuration[$key] = $cnf;
        }
        return $configuration;
    }

    /**
     * Checks if all files have the same type
     *
     * @param array $files "
     *
     * @return void
     * @throws DocumentPreviewException file types differ
     */
    protected function checkAllSame(array $files): void
    {
        $class = get_class($files[0]);
        foreach ($files as $file) {
            if (get_class($file) != $class) {
                throw new DocumentPreviewException('file types differ', 202, 400);
            }
        }
    }
}