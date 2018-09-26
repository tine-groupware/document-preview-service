<?php namespace DocumentService\DocumentConverter;


use Exception;

class Directory {
    protected $_path;

    function __construct() {
        $this->_path = Config::getInstance()->get('tempdir').uniqid('dir_', true).'/';
        mkdir($this->_path);
        return $this->_path;
    }

    function getPath(): string {
        return $this->_path;
    }

    function getFiles(string $class): array {
        $rtn = [];
        $files = scandir($this->_path);
        if (false === $files)
            throw new Exception('Scan dir failed', 50121);
        foreach ($files as $file) {
            if (!is_file($this->_path.'/'.$file)) continue;
            $rtn[] = new $class($this->_path.'/'.$file);
        }
        return $rtn;
    }

    function __destruct() {
        self::rmrf($this->_path);
    }

    protected static function rmrf(string $dir) {
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