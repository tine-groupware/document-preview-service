<?php namespace DocumentService;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\DocumentFile;
use DocumentService\DocumentConverter\File;
use DocumentService\DocumentConverter\ImageFile;
use DocumentService\DocumentConverter\PdfFile;
use Exception;


class DocumentConverter {

    //todo add logging, test, exception

    //todo refractor DocPreAction useing zend expressive 3

    public function __construct($tempDir, $logger, $config) {
        Config::getInstance()->initialize($logger, $config);
    }

    function __invoke(string $path, array $conf): array {
        $ext = pathinfo($path)['extension'];

        $conf = $this->cleanConf($conf);

        $rtn = [];

        //todo file move from docpre -> use files as referenc

        if(in_array(mb_strtolower($ext), (Config::getInstance())->get('docExt'))) {
            $file = new DocumentFile($path);

            foreach ($conf as $key => $cnf)
                $rtn[$key] = File::toBase64Array($this->convertToDoc([$file], $cnf));

        } else if (in_array(mb_strtolower($ext), (Config::getInstance())->get('pdfExt'))) {
            $file = new PdfFile($path);

            foreach ($conf as $key => $cnf)
                $rtn[$key] = File::toBase64Array($this->convertToPng([$file], $cnf));

        } else if (in_array(mb_strtolower($ext), (Config::getInstance())->get('imgExt'))) {
            $file = new ImageFile($path);

            foreach ($conf as $key => $cnf)
                $rtn[$key] = File::toBase64Array($this->convertToImage([$file], $cnf));

        } else {
            throw new Exception('file extension unknown', 40101);
        }

        return $rtn;
    }


    function convertToDoc(array $files, array $conf): array {
        if (in_array(mb_strtolower($conf['fileType']), (Config::getInstance())->get('docExt')))
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
        if ($conf['merge'] === true && count($files) > 1)
            $files = PdfFile::merge($files); //todo not tested

        if (in_array(mb_strtolower($conf['fileType']), (Config::getInstance())->get('pdfExt')))
            return $files;

        return $this->convertToPng($files, $conf);
    }


    function convertToPng(array $files, array $conf): array {
        $images = [];

        foreach ($files as $file) {
            if ($conf['firstPage'] == false)
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

    function cleanConf(array $config): array {
        $configuration = [];
        foreach ($config as $key => $conf) {
            $cnf = [
                'firstPage' => false,
                'fileType' => 'png',
                'x' => 200,
                'y' => 200,
                'color' => false,
                'merge' => false, //todo set to true when mergeing ist implemented
            ];

            if (array_key_exists('firstPage', $conf) && isset($conf['firstPage']) && ($conf['firstPage'] === 'true' || $conf['firstPage'] === true)) $cnf['firstPage'] = true;
            if (array_key_exists('firstpage', $conf) && isset($conf['firstpage']) && ($conf['firstpage'] === 'true' || $conf['firstpage'] === true)) $cnf['firstPage'] = true; //compensate inconsistent api desing
            if (array_key_exists('filetype', $conf) && isset($conf['filetype'])) $cnf['fileType'] = mb_strtolower($conf['filetype']);
            if (array_key_exists('fileType', $conf) && isset($conf['fileType'])) $cnf['fileType'] = mb_strtolower($conf['fileType']); //same as above
            if (array_key_exists('x', $conf) && isset($conf['x'])) $cnf['x'] = $conf['x'];
            if (array_key_exists('y', $conf) && isset($conf['y'])) $cnf['y'] = $conf['y'];
            if (array_key_exists('color', $conf) && isset($conf['color']) && !($conf['color'] === 'false' || $conf['color'] === false)) $cnf['color'] = mb_strtolower($conf['color']);
            if (array_key_exists('firstPage', $conf) && isset($conf['firstPage']) && ($conf['firstPage'] === 'false' || $conf['firstPage'] === false)) $cnf['firstPage'] = false;

            $configuration[$key] = $cnf;
        }
        return $configuration;
    }

    static function checkConfig(){
        return true;
    }
}