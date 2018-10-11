<?php namespace DocumentService;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\DocumentFile;
use DocumentService\DocumentConverter\File;
use DocumentService\DocumentConverter\ImageFile;
use DocumentService\DocumentConverter\PdfFile;
use Exception;

/**
 * Converts multible files to to specs
 * Class DocumentConverter
 * @package DocumentService
 */
class DocumentConverter {

    public function __construct($tempDir, $logger, $config) {
        Config::getInstance()->initialize($logger, $config);
    }

    /**
     * Converts multible files to to specs
     * @param array $paths file paths to convert
     * @param array $conf config
     * @return array base64 encoded results
     * @throws Exception
     */
    function __invoke(array $paths, array $conf): array {
        $extType = $this->getExtType($paths);

        $conf = $this->cleanConf($conf);

        $rtn = [];
        $files = [];

        //todo file move from docpre -> use files as referenc

        if(1 == $extType) {
            foreach($paths as $path) $files[] = new DocumentFile($path);

            foreach ($conf as $key => $cnf)
                $rtn[$key] = File::toBase64Array($this->convertToDoc($files, $cnf));

        } else if (2 == $extType) {
            foreach($paths as $path) $files[] = new PdfFile($path);

            foreach ($conf as $key => $cnf)
                $rtn[$key] = File::toBase64Array($this->mergePdf($files, $cnf));

        } else if (4 == $extType) {
            foreach($paths as $path) $files[] = new ImageFile($path);

            foreach ($conf as $key => $cnf)
                $rtn[$key] = File::toBase64Array($this->convertToImage($files, $cnf));

        } else {
            throw new Exception('file extension unknown', 40101);
        }

        return $rtn;
    }

    /** conversion functions, convert files and pass them on or break if the specified filetype is reached */

    function convertToDoc(array $files, array $conf): array {
        if (in_array(mb_strtolower($conf['fileType']), (Config::getInstance())->get('docExt'))) //todo ?convert between doc formats?
            return $files;

        return $this->convertToPdf($files, $conf);
    }

    function convertToPdf(array $files, array $conf): array {
        $pdfs = [];

        foreach ($files as $file)
            $pdfs[] = $file->convertToPdf();

        return $this->mergePdf($pdfs, $conf);
    }


    function mergePdf(array $files, array $conf): array {
        if (true === $conf['merge'] && count($files) > 1)
            $files = [PdfFile::merge($files)]; //todo not tested

        if (in_array(mb_strtolower($conf['fileType']), (Config::getInstance())->get('pdfExt')))
            return $files;

        return $this->convertToPng($files, $conf);
    }


    function convertToPng(array $files, array $conf): array {
        $images = [];

        foreach ($files as $file) {
            if (false == $conf['firstPage'] )
                foreach ($file->convertToPng() as $image)
                    $images[] = $image;
            else
                $images[] = $file->convertToPng()[0];
        }

        return $this->convertToImage($images, $conf);
    }


    function convertToImage(array $files, array $conf): array {
        $images = [];

        foreach ($files as $file)
            $images[] = $file->fitToSize($conf['fileType'], $conf['x'], $conf['y'], $conf['color']);

        return $images;
    }

    /**
     * Creates a clean config
     * @param array $config
     * @return array
     *
     * todo find better solution
     */
    function cleanConf(array $config): array {
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

            if (array_key_exists('firstPage', $conf) && isset($conf['firstPage']) && ($conf['firstPage'] === 'true' || $conf['firstPage'] === true)) $cnf['firstPage'] = true;
            if (array_key_exists('firstpage', $conf) && isset($conf['firstpage']) && ($conf['firstpage'] === 'true' || $conf['firstpage'] === true)) $cnf['firstPage'] = true; //compensate inconsistent api desing
            if (array_key_exists('filetype', $conf) && isset($conf['filetype'])) $cnf['fileType'] = mb_strtolower($conf['filetype']);
            if (array_key_exists('fileType', $conf) && isset($conf['fileType'])) $cnf['fileType'] = mb_strtolower($conf['fileType']); //same as above
            if (array_key_exists('x', $conf) && isset($conf['x'])) $cnf['x'] = $conf['x'];
            if (array_key_exists('y', $conf) && isset($conf['y'])) $cnf['y'] = $conf['y'];
            if (array_key_exists('color', $conf) && isset($conf['color']) && !($conf['color'] === 'false' || $conf['color'] === false)) $cnf['color'] = mb_strtolower($conf['color']);
            if (array_key_exists('merge', $conf) && isset($conf['merge']) && ($conf['merge'] === 'false' || $conf['merge'] === false)) $cnf['merge'] = false;

            $configuration[$key] = $cnf;
        }
        return $configuration;
    }

    static function checkConfig(){
        return true;
    }

    /**
     * Loops over all files and determined there extType
     * @param array $paths
     * @return int 1 := doc, 2 := pdf, 4:= img
     * @throws Exception extType of files dosnt match
     */
    function getExtType(array $paths): int {
        $extType = 7;
        foreach ($paths as $path) {
            $ext = pathinfo($path)['extension'];
            if(in_array(mb_strtolower($ext), (Config::getInstance())->get('docExt'))) {
               $extType &= 1;
            } else if (in_array(mb_strtolower($ext), (Config::getInstance())->get('pdfExt'))) {
               $extType &= 2;
            } else if (in_array(mb_strtolower($ext), (Config::getInstance())->get('imgExt'))) {
                $extType &= 4;
            } else {
                throw new Exception('file extension unknown', 40102);
            }
        }

        if ($extType == 0)
            throw new Exception('file types differ', 40103);

        return $extType;
    }
}