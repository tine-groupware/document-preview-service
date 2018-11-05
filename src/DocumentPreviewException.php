<?php declare(strict_types=1);

namespace DocumentService;

use Exception;

/**
 * Class DocumentPreviewException
 *
 * @package DocumentService
 *
 * @property int $statusCode
 */
class DocumentPreviewException extends Exception
{
    private $_statusCode;

    /**
     * DocumentPreviewException Constructor
     *
     * @param string $message    Exception message
     * @param int    $code       Exception code
     * @param int    $statusCode intended http status code
     */
    function __construct(string $message = "", int $code = 0, int $statusCode = 500)
    {
        $this->_statusCode = $statusCode;
        parent::__construct($message, $code);
    }

    /**
     * Get intended http status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }
}