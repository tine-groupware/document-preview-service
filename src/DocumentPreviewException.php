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
    private $statusCode;

    /**
     * DocumentPreviewException Constructor
     *
     * @param string $message    Exception message
     * @param int    $code       Exception code
     * @param int    $statusCode intended http status code
     */
    public function __construct(string $message = "", int $code = 0, int $statusCode = 500)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $code);
    }

    /**
     * Get intended http status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
