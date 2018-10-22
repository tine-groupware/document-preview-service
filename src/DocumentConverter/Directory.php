<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use Exception;

/**
 * Repesents an temp dir
 * Deletes dir on destruction
 *
 * @package DocumentService\DocumentConverter
 */
class Directory
{
    protected $path;

    /**
     * Directory constructor.
     *
     * @throws Exception config not initialized
     */
    function __construct()
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
    function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns all files in dir as $class
     *
     * @param string $class Fully qualified class name
     *
     * @return array of files of type $class
     * @throws Exception Scan dir failed
     */
    function getFiles(string $class): array
    {
        $rtn = [];
        $files = scandir($this->path);
        if (false === $files) {
            throw new Exception('Scan dir failed', 5000501);
        }
        foreach ($files as $file) {
            if (!is_file($this->path.'/'.$file)) {
                continue;
            }
            $rtn[] = new $class($this->path.'/'.$file);
        }
        return $rtn;
    }


    /**
     * Remove directory if no longer needed
     */
    function __destruct()
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