<?php declare(strict_types=1);

namespace DocumentService\Action;

use DocumentService\DocumentConverter\Config;
use DocumentService\ErrorHandler;
use Exception;
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
     * @throws Exception Hard Fail
     * @throws Exception logger not initialized
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        $semaphore = null;
        $semAcq = false;
        $rtn = null;

        if (extension_loaded("sysvsem")) {
            $semaphore = $this->getSem();
            $semAcq = $this->semAcquire($semaphore);
            if (false === $semAcq) {
                (ErrorHandler::getInstance())->log(6, "Service occupied", __METHOD__);
                return new TextResponse("Service occupied", 423);
            }
        }

        try {
            $conf = $this->getConf($request);
            $files = $this->getFiles($request);

            $rtn = (new DocumentConverter())($files, $conf);

        } catch (Exception $exception) {
            (ErrorHandler::getInstance())->handelException($exception);
        } finally {
            if (null !== $semaphore && true === $semAcq) {
                if (false === sem_release($semaphore)) {
                    (ErrorHandler::getInstance())->log(3, "Failed to release semaphore", __METHOD__);
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
     */
    public function __construct(array $configArray)
    {
        $config = new \Zend\Config\Config($configArray);

        $loggerOut = $config->get('loggerOut', '/dev/zero');

        if ($loggerOut instanceof Logger) {
            $logger = $loggerOut;
        } else {
            $writer = new Stream($loggerOut);
            $logger = new Logger();
            $logger->addWriter($writer);
        }

        (Config::getInstance())->initialize($config);
        (ErrorHandler::getInstance())->initialize($logger);


        $tempDir = $config->get('tempDir', 'temp/').'/';
        putenv("TMPDIR={$tempDir}");
    }

    /**
     * Extracts config
     *
     * @param ServerRequestInterface $request "
     *
     * @return array
     * @throws Exception Bad config
     */
    protected function getConf(ServerRequestInterface $request): array
    {
        if (false === isset($request->getParsedBody()["config"])) {
            throw new Exception("Bad request missing arguments", 400111);
        }
        $json = $request->getParsedBody()["config"];

        $conf = json_decode($json, true);
        if (false === $this->checkConfig()) {
            throw new Exception("Bad request JSON error", 400112);
        }
        return $conf;
    }

    /**
     * Moves uploaded files in DocumentConverter Files
     *
     * @param ServerRequestInterface $request "
     *
     * @return array
     * @throws Exception config not initialized
     * @throws config file upload error
     * @throws config file creation error
     */
    protected function getFiles(ServerRequestInterface $request): array
    {
        if (array_key_exists('file', $request->getUploadedFiles())) {
            $UploadedFiles = [$request->getUploadedFiles()['file']];
        } elseif (array_key_exists('files', $request->getUploadedFiles())) {
            $UploadedFiles = $request->getUploadedFiles()['files'];
        } else {
            throw new Exception("Parameter file or files not set", 4000103);
        }

        $files = [];
        foreach ($UploadedFiles as $UploadedFile) {
            if ($UploadedFile == null || UPLOAD_ERR_OK !== $UploadedFile->getError()) {
                throw new Exception('No File Uploaded', 4000104);
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
     * @throws Exception Config not initialized
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
     * @throws Exception config not initialized
     * @throws Exception logger not initialized
     * @throws Exception systemv fail
     */
    protected function getSem()
    {
        $ipcId = ftok(__FILE__, 'g');
        if (-1 === $ipcId) {
            (ErrorHandler::getInstance())->log(6, "Could not generate ftok", __METHOD__);
            throw new Exception('Could not generate ftok', 5000105);
        }

        $semaphore = sem_get($ipcId, (Config::getInstance())->get('maxProc'));
        if (false === $semaphore) {
            (ErrorHandler::getInstance())->log(6, "Failed not get semaphore", __METHOD__);
            throw new Exception('Failed not get semaphore', 5000106);
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