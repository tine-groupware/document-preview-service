<?php
namespace DocumentService\Action;

use Exception;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Log\Writer\Stream;
use Zend\Log\Logger;
use Zend\Config\Config;
use DocumentService\DocumentConverter;

class DocumentPreview implements MiddlewareInterface 
{
    protected $config;
    protected $logger;
    protected $tempDir;
    protected $downDir;
    protected $downUrl;
    protected $semTimeOut;
    protected $maxProc;

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // setup
        $rhost = $request->getServerParams()['REMOTE_ADDR'];

        $exts = $this->config->get('ext', array());
        if (false === is_array($exts)) {
            $exts = $exts->toArray();
        }

        // check post
        if (false === isset($request->getParsedBody()["config"])) {
            $this->logger->info("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[INFO][$rhost]Missing arguments");
            return new TextResponse(" Bad request missing arguments", 400);
        }
        $json = $request->getParsedBody()["config"];

        $conf = json_decode($json, true);
        if ( false === $this->checkConfig($conf, true)) {
            $this->logger->info("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[INFO][$rhost] JSON error: " . print_r($conf, true));
            return new TextResponse("Bad request JSON error", 400);
        }

        // magic setup

        if (array_key_exists('file', $request->getUploadedFiles()))
            $path = [$this->moveFile($request)];
        else
            $path = $this->moveFiles($request);

        if ( -1 === $path) {
            $this->logger->err("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Failed to move uploaded File");
            return new TextResponse("Internal server error - 50011", 500);
        }

        $sysvsem_enabled = extension_loaded("sysvsem");
        $semaphore = null;


        if ($sysvsem_enabled) {
            //magic
            $ipcId = ftok(__FILE__, 'g');
            if (-1 === $ipcId) {
                $this->logger->err("[DocumentPreview] " . __METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Could not generate ftok");
                return new TextResponse("Internal server error - 50012", 500);
            }

            $semaphore = sem_get($ipcId, $this->maxProc);
            if (false === $semaphore) {
                $this->logger->err("[DocumentPreview] " . __METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost]Failed not get semaphore");
                return new TextResponse("Internal server error - 50013", 500);
            }
        }

        $rtn = null;

        try {
            if ($sysvsem_enabled) {
                $semAcq = $this->semAcquire($semaphore);
                if (false === $semAcq) {
                    $this->logger->info("[DocumentPreview] " . __METHOD__ . ' ' . __LINE__ . ': ' . "[INFO][$rhost] Service occupied");
                }
            }

            try {
                $rtn = (new DocumentConverter($this->tempDir, $this->logger, $this->config))($path, $conf);
            }
            catch (Exception $exception){
                $uid = uniqid();
                $this->logger->err("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost][$uid] " . $exception->getMessage());
                return new TextResponse("Internal server error - $uid - ". $exception->getCode(), 500);
            }

        } finally {
            if (null !== $semaphore && true === $semAcq) {
                if (false === sem_release($semaphore)) {
                    $this->logger->err("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Failed to release semaphore");
                }
            }
        }

        // clean up, if file is a pdf, it was moved away, so check first!
        clearstatcache();
        if (true === is_file($path) && false === unlink($path)) {
            $this->logger->err("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Failed to unlink " . $path);
        }

        return new JsonResponse($rtn);
    }

    public function __construct($configArray)
    {
        $this->config = new Config($configArray);

        $loggerOut = $this->config->get('loggerOut', '/dev/zero');

        if ($loggerOut instanceof Logger){
            $this->logger = $loggerOut;
        }
        else {
            $writer = new Stream($loggerOut);
            $this->logger = new Logger();
            $this->logger->addWriter($writer);
        }

        $this->tempDir = $this->config->get('tempDir', 'temp/').'/';
        $this->downDir = $this->config->get('downDir', 'download/').'/';
        $this->downUrl = $this->config->get('downUrl', 'download/').'/';
        $this->semTimeOut = $this->config->get('timeOut', 30);
        $this->maxProc = $this->config->get('maxProc', 4);

        putenv("TMPDIR={$this->tempDir}");
    }

    /** @codeCoverageIgnore */
    protected function moveFile(ServerRequestInterface $request){

        $file = $request->getUploadedFiles()['file'];

        if ($file == null || UPLOAD_ERR_OK !== $file->getError()) {
            $this->logger->err("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] File upload error");
            return -1;
        }

        $path = $this->tempDir.uniqid().basename($file->getClientFilename());

        if (false === $file->moveTo($path)){ // todo change to psr7file->moveUploaded file or some thing like that
            $this->logger->err("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] Failed to move file");
            return -1;
        }

        if (false === is_file($path)){
            $this->logger->err("[DocumentPreview] ".__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] File was not moved");
            return -1;
        }

        return $path;
    }

    /** @codeCoverageIgnore */
    protected function moveFiles(ServerRequestInterface $request){
        $files = $request->getUploadedFiles()['files'];
        $paths = [];
        foreach ($files as $file) {
            if ($file == null || UPLOAD_ERR_OK !== $file->getError()) {
                $this->logger->err("[DocumentPreview] " . __METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] File upload error");
                return -1;
            }

            $path = $this->tempDir . uniqid() . basename($file->getClientFilename());

            if (false === $file->moveTo($path)) { // todo change to psr7file->moveUploaded file or some thing like that
                $this->logger->err("[DocumentPreview] " . __METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] Failed to move file");
                return -1;
            }

            if (false === is_file($path)) {
                $this->logger->err("[DocumentPreview] " . __METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] File was not moved");
                return -1;
            }

            $paths[] = $path;
        }
        return $paths;
    }

    protected function semAcquire($semaphore){
        $timeStarted = time();
        do {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $semAcq = sem_acquire($semaphore, true);
            usleep(10000);
        } while (false === $semAcq && time() - $timeStarted < $this->semTimeOut);
        return $semAcq;
    }

    protected function checkConfig($conf, $extended){
        if (false === is_array($conf)) {
            return false;
        }
        if (true === $extended) {
            /** @codeCoverageIgnoreStart */
            return DocumentConverter::checkConfig($conf);
            /** @codeCoverageIgnoreEnd */
        }
        return true;
    }
}