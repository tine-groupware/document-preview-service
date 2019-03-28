<?php declare(strict_types=1);

namespace DocumentService\Action;

use DocumentService\BadRequestException;
use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentPreviewException;
use DocumentService\ErrorHandler;
use DocumentService\ExtensionDoseNotMatchMineTypeException;
use DocumentService\Lock;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Log\Formatter\Simple;
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
        (ErrorHandler::getInstance())->dlog("Client Connected", __METHOD__);

        $lock = null;
        $semAcq = false;
        $rtn = null;

        (ErrorHandler::getInstance())->setRequest($request);

        try {
            $conf = $this->getConf($request);
            $files = $this->getFiles($request);

            //priority queuing

            $synchronRequest = array_key_exists('synchronRequest', $conf) && $conf['synchronRequest'];

            if ($synchronRequest) {
                unset($conf['synchronRequest']);
            }

            if (extension_loaded("sysvsem")) {
                $lock = new Lock(
                    $synchronRequest,
                    (Config::getInstance())->get('maxProc'),
                    (Config::getInstance())->get('maxProcHighPrio')
                );
                $semAcq = $this->lockAcquire($lock);
                if (false === $semAcq) {
                    (ErrorHandler::getInstance())->dlog("Error: Service occupied", __METHOD__);
                    (ErrorHandler::getInstance())->log(Logger::INFO, "Service occupied", __METHOD__);
                    return new TextResponse("Service occupied", 423);
                }
            }

            $startTime = microtime(true);

            $rtn = (new DocumentConverter())($files, $conf);

            (ErrorHandler::getInstance())->log(Logger::DEBUG, "Converted files in " .
                (string)(microtime(true) - $startTime) . " seconds.", $files[0]->getMd5Hash());
        } catch (ExtensionDoseNotMatchMineTypeException $exception) {
            (ErrorHandler::getInstance())->dlog("Error: ExtensionDoseNotMatchMineTypeException", __METHOD__);
            (ErrorHandler::getInstance())->log(Logger::INFO, $exception->getMessage(), $exception->getCode());
            return (ErrorHandler::getInstance())->getResponse($exception);
        } catch (BadRequestException $exception) {
            (ErrorHandler::getInstance())->dlog("Error: BadRequestException", __METHOD__);
            (ErrorHandler::getInstance())->log(Logger::INFO, $exception->getMessage(), $exception->getCode());
            return (ErrorHandler::getInstance())->getResponse($exception);
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

        (ErrorHandler::getInstance())->dlog("Success", __METHOD__);

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

        $dloggerOut = $config->get('dloggerOut', '/dev/zero');
        if ($dloggerOut !== '/dev/zero') {
            $dwriter = new Stream($dloggerOut);
            $dfilter = new \Zend\Log\Filter\Priority($config->get('dlogLevel', Logger::DEBUG));
            $dwriter->addFilter($dfilter);
            $formatter = new Simple('%message%,' . PHP_EOL);
            $dwriter->setFormatter($formatter);
            $dlogger = new Logger();
            $dlogger->addWriter($dwriter);
            (ErrorHandler::getInstance())->setDlogger($dlogger);
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
     * @throws BadRequestException Bad config
     */
    protected function getConf(ServerRequestInterface $request): array
    {
        if (false === isset($request->getParsedBody()["config"])) {
            throw new BadRequestException("Bad request missing arguments", 111, 400);
        }
        $json = $request->getParsedBody()["config"];

        $conf = json_decode($json, true);
        if (false === $this->checkConfig()) {
            throw new BadRequestException("Bad request JSON error", 112, 400);
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
     * @throws BadRequestException file upload error
     * @throws BadRequestException file creation error
     * @throws ExtensionDoseNotMatchMineTypeException
     */
    protected function getFiles(ServerRequestInterface $request): array
    {
        if (array_key_exists('file', $request->getUploadedFiles())) {
            $UploadedFiles = [$request->getUploadedFiles()['file']];
        } elseif (array_key_exists('files', $request->getUploadedFiles())) {
            $UploadedFiles = $request->getUploadedFiles()['files'];
        } else {
            throw new BadRequestException("Parameter file or files not set", 103, 400);
        }


        $files = [];
        foreach ($UploadedFiles as $UploadedFile) {
            if (null == $UploadedFile || UPLOAD_ERR_OK !== $UploadedFile->getError()) {
                throw new BadRequestException('No File Uploaded', 104, 400);
            }
            $path = (Config::getInstance())->get('tempdir') . 'upload' . uniqid()
                . basename($UploadedFile->getClientFilename());

            (ErrorHandler::getInstance())->dlog(['message' => 'Uploaded file', 'path' => $path], __METHOD__);

            $UploadedFile->moveTo($path);

            $file = new File($path, true);

            $files[] = $file;
        }
        return $files;
    }


    /**
     * @param $lock Lock
     * @return bool true if lock acquire
     * @throws DocumentPreviewException config not initialised
     */
    protected function lockAcquire($lock): bool
    {
        $timeStarted = time();
        do {
            $semAcq = $lock->lock();
            usleep(10000);
        } while (false === $semAcq && time() - $timeStarted < (Config::getInstance())->get('semTimeOut'));
        return $semAcq;
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
