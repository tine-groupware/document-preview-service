<?php declare(strict_types=1);



namespace DocumentService;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Raven_Client;
use Zend\Diactoros\Response\TextResponse;
use Zend\Log\Logger;

/**
 * Class ErrorHandler
 *
 * @package DocumentService
 *
 * @property Raven_Client $_sentryClient
 * @property \Zend\Log\Logger $_logger
 * @property ServerRequestInterface $request
 */
class ErrorHandler
{
    private static $_instance = null;
    private $_logger = null;
    private $_sentryClient = null;
    private $request = null;
    private $_uid;

    /**
     * Singleton
     */
    protected function __clone()
    {
    }

    /**
     * Singleton
     */
    protected function __construct()
    {
        $this->_uid = uniqid('', true);
    }

    /**
     * Singleton
     *
     * @return ErrorHandler
     */
    public static function getInstance(): ErrorHandler
    {
        if (null === self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Set logger
     *
     * @param \Zend\Log\Logger $logger "
     *
     * @return void
     */
    public function setLogger($logger): void
    {
        $this->_logger = $logger;
    }

    /**
     * Set request
     *
     * @param ServerRequestInterface $request "
     *
     * @return void
     */
    public function setRequest($request): void
    {
        $this->request = $request;
    }


    /**
     * Set Sentry Client
     *
     * @param Raven_Client $sentryClient "
     *
     * @return void
     */
    public function setSentryClient($sentryClient): void
    {
        $this->_sentryClient = $sentryClient;
    }

    /**
     * Logs Exception and returns error response
     *
     * @param Exception $exception "
     *
     * @return ResponseInterface
     */
    public function handelException(DocumentPreviewException $exception): ResponseInterface
    {
        if (null !== $this->_logger) {
            $this->log($exception->getStatusCode() < 400 ? Logger::INFO : Logger::ALERT, $exception->getMessage(), $exception->getCode());
        }
        if (null !== $this->_sentryClient) {
            $this->_sentryClient->captureException($exception);
        }
        return $this->getResponse($exception);
    }

    /**
     * Returns error response
     *
     * @param Exception $exception "
     *
     * @return ResponseInterface
     */
    public function getResponse(DocumentPreviewException $exception): ResponseInterface
    {
        $message = 'Internal server error';
        $code = $exception->getCode();
        $status = $exception->getStatusCode();
        if ($status < 400) {
            $message = $exception->getMessage();
        }
        return new TextResponse("$message - $code - $this->_uid", $status);
    }

    /**
     * Writes log to log file
     *
     * @param int    $priority syslog priority
     * @param string $message  message to log
     * @param string $source   Error code or __METHOD__
     *
     * @return void
     */
    public function log($priority, $message, $source = ""): void
    {
        if (null !== $this->_logger) {
            $this->_logger->log($priority, "[$priority][$this->_uid][".$this->request->getAttribute('certInfo')."][$source] $message");
        }
    }
}