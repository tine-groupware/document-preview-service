<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentPreviewException;

/**
 * Singleton wrapper for zend config
 *
 * @package DocumentService\DocumentConverter
 *
 * @property \Zend\Config\Config $config
 */
class Config
{
    private static $instance = null;
    private $initialized = false;
    private $config;


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
    public function initialize(\Zend\Config\Config $config): void
    {
        $this->initialized = true;
        $this->config = $config;
    }

    /**
     * Returns Value for key
     *
     * @param string $arg key
     *
     * @return int|string|array
     * @throws DocumentPreviewException not initialized
     */
    public function get(string $arg)
    {
        if (true !== $this->initialized) {
            throw new DocumentPreviewException("Not initialized", 401, 500);
        }
        switch ($arg) {
            case 'docExt':
                return $this->config->get('docExt', new \Zend\Config\Config(['txt', 'odt', 'docx']))->toArray();
            case 'pdfExt':
                return $this->config->get('pdfExt', new \Zend\Config\Config(['pdf', 'ps']))->toArray();
            case 'imgExt':
                return $this->config->get(
                    'imgExt',
                    new \Zend\Config\Config(['jpg', 'jpeg', 'gif', 'tiff', 'png'])
                )->toArray();
            case 'tempdir':
                $dir = $this->config->get('tempDir', '/tmp');
                if (substr($dir, -1) !== '/') {
                    $dir .= '/';
                }
                return $dir;
            case 'ooBinary':
                return $this->config->get('ooBinary', 'soffice');
            case 'semTimeOut':
                return $this->config->get('timeOut', 30);
            case 'maxProc':
                return $this->config->get('maxProc', 4);
            case 'maxProcHighPrio':
                return $this->config->get('maxProcHighPrio', 4);
            case 'extToMime':
                return $this->config->get('extToMime', new \Zend\Config\Config(['txt' => 'text/plain']))->toArray();
            default:
                return $this->config->get($arg);
        }
    }
}
