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
    protected $original;

    /**
     * File constructor.
     *
     * @param string $path "
     * @param bool $reference "
     *
     * @param string|null $format
     * @param bool $original
     * @throws DocumentPreviewException
     * @throws ExtensionDoseNotMatchMineTypeException
     */
    public function __construct(string $path, bool $reference = false, string $format = null, $original = false)
    {
        if (!is_file($path) && !is_readable($path)) {
            throw new DocumentPreviewException("Not a readable file", 701, 500);
        }

        (ErrorHandler::getInstance())->dlog(
            ["message" => "Call to create File", "reference" => $reference, "path" => $path],
            __METHOD__,
            true
        );

        $this->original = $original;

        $ext =  strtolower(pathinfo($path)['extension']);

        if (null === $format) {
            $format = $ext;
        }
        $this->format = $format;

        if (array_key_exists(strtolower($ext), (Config::getInstance())->get('extToMime'))) {
            $mime_type = mime_content_type($path);
            if (false == in_array($mime_type, (Config::getInstance())->get('extToMime')[$ext])) {
                if ($mime_type !== "application/octet-stream") {

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

                (ErrorHandler::getInstance())->log(
                    Logger::NOTICE,
                    "bad mimetype:: path: " . $path . " has mimetype application/octet-stream, but extension is $ext.",
                    __METHOD__
                );
            }
        } else {
            (ErrorHandler::getInstance())->dlog(
                [
                    "message" => "Unmapped extension",
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

    public function isOriginal() {
        return $this->original;
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
