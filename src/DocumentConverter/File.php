<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use Zend\Log\Logger;

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
     * @throws DocumentPreviewException Not a readable file
     * @throws DocumentPreviewException config not initialized
     */
    public function __construct(string $path, bool $reference = false)
    {
        if (!is_file($path) && !is_readable($path)) {
            throw new DocumentPreviewException("Not a readable file", 701, 500);
        }

        $ext = pathinfo($path)['extension'];

        if (array_key_exists($ext, (Config::getInstance())->get('extToMime'))) {
            if ((Config::getInstance())->get('extToMime')[$ext] == mime_content_type($path)
            ) {
                if ($reference) {
                    unlink($path);
                }
                (ErrorHandler::getInstance())->log(Logger::DEBUG, "path: " . $path, __METHOD__);
                (ErrorHandler::getInstance())->log(Logger::DEBUG, "mime-type: " . mime_content_type($path), __METHOD__);
                throw new DocumentPreviewException("Extension dose not match mime-type", 703, 422);
            }
        } else {
            (ErrorHandler::getInstance())->log(Logger::INFO, "Unmaped extension " . $ext, __METHOD__);
            (ErrorHandler::getInstance())->log(Logger::INFO, (Config::getInstance())->get('extToMime'), __METHOD__);
        }

        $this->reference = $reference;

        if (false === $reference) {
            $this->path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.pathinfo($path)['extension'];
            copy($path, $this->path);
        } else {
            $this->path = $path;
        }
    }


    /**
     * Returns filesystem path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }


    /**
     * Returns base64 encoded file
     *
     * @return string
     */
    public function getBase64(): string
    {
        return base64_encode(file_get_contents($this->path));
    }

    /**
     * Returns md5 hash of file
     *
     * @return string
     */
    public function getMd5Hash(): string
    {
        return md5_file($this->path);
    }

    /**
     * Remove file, when no longer needed
     */
    public function __destruct()
    {
        unlink($this->path);
    }


    /**
     * Encodes all files to base64
     *
     * @param array $files array of files
     *
     * @return array
     */
    public static function toBase64Array(array $files): array
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
     * @throws DocumentPreviewException Exception Not a readable file
     * @throws DocumentPreviewException config not initialized
     * @throws DocumentPreviewException file extension unknown
     */
    public static function fromPath(string $path): File
    {
        $ext = pathinfo($path)['extension'];
        if (in_array(mb_strtolower($ext), (Config::getInstance())->get('docExt'))) {
            return new DocumentFile($path);
        } elseif (in_array(mb_strtolower($ext), (Config::getInstance())->get('pdfExt'))) {
            return new PdfFile($path);
        } elseif (in_array(mb_strtolower($ext), (Config::getInstance())->get('imgExt'))) {
            return new ImageFile($path);
        } else {
            throw new DocumentPreviewException('file extension unknown', 702, 415);
        }
    }
}