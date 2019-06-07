<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter\FileSystem;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentPreviewException;

/**
 * Repesents an temp dir
 * Deletes dir on destruction
 *
 * @package DocumentService\DocumentConverter
 */
class Directory
{
    private $path;

    /**
     * Directory constructor.
     *
     * @throws DocumentPreviewException config not initialized
     */
    public function __construct()
    {
        $this->path = Config::getInstance()->get('tempdir').uniqid('dir_', true).'/';
        mkdir($this->path);
        return $this->path;
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
     * Returns all files in dir as $class
     *
     * @param string $class Fully qualified class name
     *
     * @return array of files of type $class
     * @throws DocumentPreviewException Scan dir failed
     */
    public function getFiles(string $format): array
    {
        $rtn = [];
        $files = scandir($this->path);
        if (false === $files) {
            throw new DocumentPreviewException('Scan dir failed', 501, 500);
        }
        foreach ($files as $file) {
            if (!is_file($this->path.'/'.$file)) {
                continue;
            }
            $f = new File($this->path.'/'.$file, false, $format);
            if (null === $f) {
                throw new DocumentPreviewException('Cound not load file', 502, 500);
            }
            $rtn[] = $f;
        }
        if ([] == $rtn) {
            throw new DocumentPreviewException('No files found in dir', 503, 500);
        }
        return $rtn;
    }


    /**
     * Remove directory if no longer needed
     */
    public function __destruct()
    {
        self::rmrf($this->path);
    }


    /**
     * Remove directory recursive
     *
     * @param string $dir path to dir
     *
     * @return void
     */
    protected static function rmrf(string $dir): void
    {
        $dir = rtrim($dir, '/') . '/';

        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                $file = $dir.$file;
                if (is_dir($file)) {
                    self::rmrf($file);
                } else {
                    unlink($file);
                }
            }
            closedir($dh);
            rmdir($dir);
        }
    }
}
