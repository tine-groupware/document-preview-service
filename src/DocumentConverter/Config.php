<?php namespace DocumentService\DocumentConverter;

use Exception;

/**
 * Singleton wrapper for zend config
 * @package DocumentService\DocumentConverter
 */
class Config
{
    protected static $_instance = null;
    protected $_initialized = false;
    protected $_config;
    protected $_logger;

    public static function getInstance()
    {
        if (null === self::$_instance)
        {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    protected function __clone() {}

    protected function __construct() {}

    function initialize($logger, $config){
        // if (true === $this->_initialized) throw new Exception('Already initialize', 5111); // reinit should be allowed?
        $this->_initialized = true;
        $this->_config = $config;
        $this->_logger = $logger;
    }

    /**
     * @returns array or string
     */
    function get($arg) {
        if (true !== $this->_initialized) throw new Exception("Not initialized", 5112);
        switch ($arg) {
            case 'docExt':
                return $this->_config->get('docExt', new \Zend\Config\Config(['txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx']))->toArray();
            case 'pdfExt':
                return $this->_config->get('pdfExt', new \Zend\Config\Config(['pdf', 'ps']))->toArray();
            case 'imgExt':
                return $this->_config->get('imgExt', new \Zend\Config\Config(['jpg', 'jpeg', 'gif', 'tiff', 'png']))->toArray();
            case 'tempdir':
                $dir = $this->_config->get('tempDir', '/tmp');
                if (substr($dir, 1) !== '/')
                    $dir .= '/';
                return $dir;
            case 'ooBinary':
                return $this->_config->get('ooBinary', 'soffice');
            default:
                return $this->_config->get($arg);
        }
    }

    function logger(){
        if (true !== $this->_initialized) throw new Exception('Not initialize', 5113);
        return $this->_logger;
    }
}