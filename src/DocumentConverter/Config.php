<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use Exception;
use phpDocumentor\Reflection\Types\Mixed;

/**
 * Singleton wrapper for zend config
 *
 * @package DocumentService\DocumentConverter
 *
 * @property \Zend\Config\Config $_config
 */
class Config
{
    protected static $instance = null;
    protected $initialized = false;
    protected $config;


    /**
     * Singleton
     *
     * @return Config
     */
    public static function getInstance(): Config
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * Singleton
     *
     * @return void
     */
    protected function __clone()
    {
    }


    /**
     * Singleton
     */
    protected function __construct()
    {
    }


    /**
     * Set config value
     *
     * @param \Zend\Config\Config $config "
     *
     * @return void
     */
    function initialize(\Zend\Config\Config $config): void
    {
        $this->initialized = true;
        $this->_config = $config;
    }

    /**
     * Returns Value for key
     *
     * @param string $arg key
     *
     * @return int|string|array
     * @throws Exception not initialized
     */
    function get(string $arg)
    {
        if (true !== $this->initialized) {
            throw new Exception("Not initialized", 5000401);
        }
        switch ($arg) {
        case 'docExt':
            return $this->_config->get('docExt', new \Zend\Config\Config(['txt', 'rtf', 'odt', 'ott', 'ods', 'ots', 'odp', 'otp', 'xls', 'xlt', 'xlsx', 'xltx', 'doc', 'dot', 'docx', 'dotx', 'ppt', 'pot', 'pptx', 'potx']))->toArray();
        case 'pdfExt':
            return $this->_config->get('pdfExt', new \Zend\Config\Config(['pdf', 'ps']))->toArray();
        case 'imgExt':
            return $this->_config->get('imgExt', new \Zend\Config\Config(['jpg', 'jpeg', 'gif', 'tiff', 'png']))->toArray();
        case 'tempdir':
            $dir = $this->_config->get('tempDir', '/tmp');
            if (substr($dir, 1) !== '/') {
                $dir .= '/';
            }
            return $dir;
        case 'ooBinary':
            return $this->_config->get('ooBinary', 'soffice');
        case 'semTimeOut':
            return $this->_config->get('timeOut', 30);
        case 'maxProc':
            return $this->_config->get('maxProc', 4);
        default:
            return $this->_config->get($arg);
        }
    }
}