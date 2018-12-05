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
 * @property Raven_Client $sentryClient
 * @property \Zend\Log\Logger $logger
 * @property ServerRequestInterface $request
 */
class ErrorHandler
{
    private static $instance = null;
    private $logger = null;
    private $sentryClient = null;
    private $request = null;
    private $uid;

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
        $this->uid = uniqid('', true);
    }

    /**
     * Singleton
     *
     * @return ErrorHandler
     */
    public static function getInstance(): ErrorHandler
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
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
        $this->logger = $logger;
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
        $this->sentryClient = $sentryClient;
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
        if (null !== $this->logger) {
            $this->log(
                $exception->getStatusCode() < 400 ? Logger::INFO : Logger::ALERT,
                $exception->getMessage(),
                $exception->getCode()
            );
        }
        if (null !== $this->sentryClient) {
            $this->sentryClient->captureException($exception);
        }
        return $this->getResponse($exception);
    }

    /**
     * Returns error response
     *
     * @param DocumentPreviewException $exception "
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
        return new TextResponse("$message - $code - $this->uid", $status);
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
        if (null !== $this->logger) {
            $this->logger->log(
                $priority,
                "[$priority][$this->uid][".$this->request->getAttribute('certInfo')[0]."][$source] $message"
            );
        }
    }
}