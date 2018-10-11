<?php namespace DocumentService\DocumentConverter;

use Exception;

abstract class File {
    protected $_path;
    protected $_reference;

    function __construct(string $path, bool $reference = false) {
        if (!is_file($path) && !is_readable($path)) {
            throw new Exception("Not a readable file - $path", 50141);
        }

        $this->_reference = $reference;

        if (!$reference) {
            $this->_path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.pathinfo($path)['extension'];
            copy($path, $this->_path);
        } else {
            $this->_path = $path;
        }
    }

    function getPath(): string {
        return $this->_path;
    }

    function getBase64(): string {
        return base64_encode(file_get_contents($this->_path));
    }

    function __destruct() {
        unlink($this->_path);
    }

    static function toBase64Array(array $files): array {
        $rtn = [];
        foreach ($files as $file)
            $rtn[] = $file->getBase64();
        return $rtn;
    }
}