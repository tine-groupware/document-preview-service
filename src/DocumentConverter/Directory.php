<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentPreviewException;

/**
 * Repesents an temp dir
 * Deletes dir on destruction
 *
 * @package DocumentService\DocumentConverter
 */
class Directory
{
    private $_path;

    /**
     * Directory constructor.
     *
     * @throws DocumentPreviewException config not initialized
     */
    function __construct()
    {
        $this->_path = Config::getInstance()->get('tempdir').uniqid('dir_', true).'/';
        mkdir($this->_path);
        return $this->_path;
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
     * Returns all files in dir as $class
     *
     * @param string $class Fully qualified class name
     *
     * @return array of files of type $class
     * @throws DocumentPreviewException Scan dir failed
     */
    function getFiles(string $class): array
    {
        $rtn = [];
        $files = scandir($this->_path);
        if (false === $files) {
            throw new DocumentPreviewException('Scan dir failed', 501, 500);
        }
        foreach ($files as $file) {
            if (!is_file($this->_path.'/'.$file)) {
                continue;
            }
            $rtn[] = new $class($this->_path.'/'.$file);
        }
        return $rtn;
    }


    /**
     * Remove directory if no longer needed
     */
    function __destruct()
    {
        self::rmrf($this->_path);
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
        foreach (glob($dir) as $file) {
            if (is_dir($file)) {
                self::rmrf("$file/*");
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }
}