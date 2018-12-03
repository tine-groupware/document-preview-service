<?php declare(strict_types=1);

namespace DocumentService\Action;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use DocumentService\Lock;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Log\Writer\Stream;
use Zend\Log\Logger;
use DocumentService\DocumentConverter;
use Psr\Http\Message\ResponseInterface;

class DocumentPreview implements MiddlewareInterface
{

    /**
     * Process
     *
     * @param ServerRequestInterface  $request  "
     * @param RequestHandlerInterface $delegate "
     *
     * @return ResponseInterface
     * @throws DocumentPreviewException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        $lock = null;
        $semAcq = false;
        $rtn = null;

        (ErrorHandler::getInstance())->setRequest($request);

        try {
            $conf = $this->getConf($request);

            if (extension_loaded("sysvsem")) {
                $lock = new Lock(
                    array_key_exists('synchronRequest', $conf) && $conf['synchronRequest'],
                    (Config::getInstance())->get('maxProc'),
                    (Config::getInstance())->get('maxProcHighPrio')
                );
                $semAcq = $lock->lock();
                if (false === $semAcq) {
                    (ErrorHandler::getInstance())->log(Logger::INFO, "Service occupied", __METHOD__);
                    return new TextResponse("Service occupied", 423);
                }
            }

            $files = $this->getFiles($request);

            $startTime = microtime(true);

            $rtn = (new DocumentConverter())($files, $conf);

            (ErrorHandler::getInstance())->log(Logger::DEBUG, "Converted files in ".
                (string)(microtime(true) - $startTime) ." seconds.", $files[0]->getMd5Hash());

        } catch (DocumentPreviewException $exception) {
            return (ErrorHandler::getInstance())->handelException($exception);
        } finally {
            if (null !== $lock && true === $semAcq) {
                if (false === $lock->unlock()) {
                    (ErrorHandler::getInstance())->log(Logger::ERR, "Failed to release semaphore", __METHOD__);
                }
            }
        }

        clearstatcache();

        return new JsonResponse($rtn);
    }


    /**
     * DocumentPreview constructor.
     *
     * @param array $configArray "
     *
     * @throws DocumentPreviewException
     */
    public function __construct(array $configArray)
    {
        $config = new \Zend\Config\Config($configArray);

        $loggerOut = $config->get('loggerOut', '/dev/zero');

        if ($loggerOut instanceof Logger) {
            $logger = $loggerOut;
        } else {
            $writer = new Stream($loggerOut);
            $filter = new \Zend\Log\Filter\Priority($config->get('logLevel', Logger::NOTICE));
            $writer->addFilter($filter);
            $logger = new Logger();
            $logger->addWriter($writer);
        }

        (Config::getInstance())->initialize($config);
        (ErrorHandler::getInstance())->setLogger($logger);

        if (!is_writable($config->get('tempDir', 'temp/'))) {
            throw new DocumentPreviewException("Temp dir is not writable", 110, 500);
        }

        putenv("TMPDIR={$config->get('tempDir', 'temp/')}");
    }

    /**
     * Extracts config
     *
     * @param ServerRequestInterface $request "
     *
     * @return array
     * @throws DocumentPreviewException Bad config
     */
    protected function getConf(ServerRequestInterface $request): array
    {
        if (false === isset($request->getParsedBody()["config"])) {
            throw new DocumentPreviewException("Bad request missing arguments", 111, 400);
        }
        $json = $request->getParsedBody()["config"];

        $conf = json_decode($json, true);
        if (false === $this->checkConfig()) {
            throw new DocumentPreviewException("Bad request JSON error", 112, 400);
        }
        return $conf;
    }

    /**
     * Moves uploaded files in DocumentConverter Files
     *
     * @param ServerRequestInterface $request "
     *
     * @return array
     * @throws DocumentPreviewException config not initialized
     * @throws DocumentPreviewException config file upload error
     * @throws DocumentPreviewException config file creation error
     */
    protected function getFiles(ServerRequestInterface $request): array
    {
        if (array_key_exists('file', $request->getUploadedFiles())) {
            $UploadedFiles = [$request->getUploadedFiles()['file']];
        } elseif (array_key_exists('files', $request->getUploadedFiles())) {
            $UploadedFiles = $request->getUploadedFiles()['files'];
        } else {
            throw new DocumentPreviewException("Parameter file or files not set", 103, 400);
        }


        $files = [];
        foreach ($UploadedFiles as $UploadedFile) {
            if (null == $UploadedFile || UPLOAD_ERR_OK !== $UploadedFile->getError()) {
                throw new DocumentPreviewException('No File Uploaded', 104, 400);
            }
            $path = (Config::getInstance())->get('tempDir') . uniqid() . basename($UploadedFile->getClientFilename());
            $UploadedFile->moveTo($path);
            $file = DocumentConverter\File::fromPath($path);
            unlink($path);
            $files[] = $file;
        }
        return $files;
    }

    /**
     * Acquire Semaphore
     *
     * @param resource $semaphore "
     *
     * @return bool
     *
     * @throws DocumentPreviewException Config not initialized
     */
    protected function semAcquire($semaphore): bool
    {
        $timeStarted = time();
        do {
            $semAcq = sem_acquire($semaphore, true);
            usleep(10000);
        } while (false === $semAcq && time() - $timeStarted < (Config::getInstance())->get('semTimeOut'));
        return $semAcq;
    }

    /**
     * Init Semaphore
     *
     * @return resource semaphore
     * @throws DocumentPreviewException config not initialized
     * @throws DocumentPreviewException logger not initialized
     * @throws DocumentPreviewException systemv fail
     */
    protected function getSem()
    {
        $ipcId = ftok(__FILE__, 'g');
        if (-1 === $ipcId) {
            (ErrorHandler::getInstance())->log(Logger::ERR, "Could not generate ftok", __METHOD__);
            throw new DocumentPreviewException('Could not generate ftok', 105, 500);
        }

        $semaphore = sem_get($ipcId, (Config::getInstance())->get('maxProc'));
        if (false === $semaphore) {
            (ErrorHandler::getInstance())->log(Logger::ERR, "Failed not get semaphore", __METHOD__);
            throw new DocumentPreviewException('Failed not get semaphore', 106, 500);
        }
        return $semaphore;
    }

    /**
     * Check config, also done in documentConverter
     *
     * @return bool
     */
    protected function checkConfig(): bool
    {
        //todo
        return true;
    }
}