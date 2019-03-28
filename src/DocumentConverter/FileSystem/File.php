<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter\FileSystem;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use DocumentService\ExtensionDoseNotMatchMineTypeException;
use Zend\Log\Logger;

class File
{
    protected $path;
    protected $reference;
    protected $format;

    /**
     * File constructor.
     *
     * @param string $path "
     * @param bool $reference "
     *
     * @param string|null $format
     */
    public function __construct(string $path, bool $reference = false, string $format = null)
    {
        if (!is_file($path) && !is_readable($path)) {
            throw new DocumentPreviewException("Not a readable file", 701, 500);
        }

        (ErrorHandler::getInstance())->dlog(
            ["message" => "Call to create File", "reference" => $reference, "path" => $path],
            __METHOD__,
            true
        );

        $ext =  strtolower(pathinfo($path)['extension']);

        if (null === $format) {
            $format = $ext;
        }
        $this->format = $format;

        if (array_key_exists(strtolower($ext), (Config::getInstance())->get('extToMime'))) {
            if ((Config::getInstance())->get('extToMime')[$ext] != mime_content_type($path)
            ) {
                $mime_type = mime_content_type($path);
                if ($reference) {
                    unlink($path);
                }

                (ErrorHandler::getInstance())->log(
                    Logger::DEBUG,
                    "path: " . $path . " mimetype: \"" . $mime_type . "\"",
                    __METHOD__
                );

                throw new ExtensionDoseNotMatchMineTypeException($ext, $mime_type, 703);
            }
        } else {
            (ErrorHandler::getInstance())->dlog(
                [
                    "message" => "Unmaped extension",
                    "ext" => $ext,
                    "path" => $path,
                    "extMime" => (Config::getInstance())->get('extToMime'),
                    "realMime" => mime_content_type($path)
                ],
                __METHOD__,
                true
            );
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

        (ErrorHandler::getInstance())->dlog(
            ["message" => "Created file", "reference" => $reference, "path" => $path],
            __METHOD__,
            true
        );
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

    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Remove file, when no longer needed
     */
    public function __destruct()
    {
        unlink($this->path);
        (ErrorHandler::getInstance())->dlog(
            ["message" => "Deleted file", "reference" => $this->reference, "path" => $this->path],
            __METHOD__,
            true
        );
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
}
