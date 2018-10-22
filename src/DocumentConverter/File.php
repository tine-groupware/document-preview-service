<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use Exception;

abstract class File
{
    protected $path;
    protected $reference;

    /**
     * File constructor.
     *
     * @param string $path      "
     * @param bool   $reference "
     *
     * @throws Exception Not a readable file
     * @throws Exception config not initialized
     */
    function __construct(string $path, bool $reference = false)
    {
        if (!is_file($path) && !is_readable($path)) {
            throw new Exception("Not a readable file", 5000701);
        }

        $this->_reference = $reference;

        if (!$reference) {
            $this->_path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.pathinfo($path)['extension'];
            copy($path, $this->_path);
        } else {
            $this->_path = $path;
        }
    }


    /**
     * Returns filesystem path
     *
     * @return string
     */
    function getPath(): string
    {
        return $this->_path;
    }


    /**
     * Returns base64 encoded file
     *
     * @return string
     */
    function getBase64(): string
    {
        return base64_encode(file_get_contents($this->_path));
    }


    /**
     * Remove file, when no longer needed
     */
    function __destruct()
    {
        unlink($this->_path);
    }


    /**
     * Encodes all files to base64
     *
     * @param array $files array of files
     *
     * @return array
     */
    static function toBase64Array(array $files): array
    {
        $rtn = [];
        foreach ($files as $file) {
            $rtn[] = $file->getBase64();
        }
        return $rtn;
    }

    /**
     * Creates a new File the class is selected based on the extention
     *
     * @param string $path "
     *
     * @return File
     * @throws Exception Exception Not a readable file
     * @throws Exception config not initialized
     * @throws Exception file extension unknown
     */
    static function fromPath(string $path): File
    {
        $ext = pathinfo($path)['extension'];
        if (in_array(mb_strtolower($ext), (Config::getInstance())->get('docExt'))) {
            return new DocumentFile($path);
        } else if (in_array(mb_strtolower($ext), (Config::getInstance())->get('pdfExt'))) {
            return new PdfFile($path);
        } else if (in_array(mb_strtolower($ext), (Config::getInstance())->get('imgExt'))) {
            return new ImageFile($path);
        } else {
            throw new Exception('file extension unknown', 4150702);
        }
    }
}