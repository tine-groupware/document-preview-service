<?php declare(strict_types=1);



namespace DocumentService;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\TextResponse;

/**
 * Class ErrorHandler
 * @package DocumentService
 * @property \Zend\Log\Logger $logger
 */
class ErrorHandler
{
    protected static $instance = null;
    protected $initialized = false;
    protected $logger;
    protected $uid;

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
    public function initialize($logger): void
    {
        $this->initialized = true;
        $this->logger = $logger;
    }



    /**
     * Logs Exception and returns error response
     *
     * @param Exception $exception "
     *
     * @return ResponseInterface
     * @throws Exception not initialized
     */
    public function handelException(Exception $exception): ResponseInterface
    {
        if (true !== $this->initialized) {
            throw new Exception("Not initialized", 5000301);
        }

        $this->log($exception->getCode() < 40000 ? 6 : 2, $exception->getMessage(), $exception->getCode());
        return $this->getResponse($exception);
    }

    /**
     * Returns error response
     *
     * @param Exception $exception "
     *
     * @return ResponseInterface
     * @throws Exception not initialized
     */
    public function getResponse(Exception $exception): ResponseInterface
    {
        if (true !== $this->initialized) {
            throw new Exception("Not initialized", 5000302);
        }

        $message = 'Internal server error';
        $code = $exception->getCode();
        $status = intval($exception->getCode()/100);
        if ($code < 40000) {
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
     * @throws Exception not initialized
     */
    public function log($priority, $message, $source = ""): void
    {
        if (true !== $this->initialized) {
            throw new Exception("Not initialized", 5000303);
        }
        $this->logger->log($priority, "[$priority][$this->uid][$source] $message");
    }
}