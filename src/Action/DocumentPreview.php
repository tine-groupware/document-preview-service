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
use Zend\Diactoros\Exception\UploadedFileErrorException;
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
                // gets unlocked with lock destruction
                $semAcq = $lock->lock((Config::getInstance())->get('semTimeOut'));
                if (false === $semAcq) {
                    (ErrorHandler::getInstance())->dlog("Error: Service occupied", __METHOD__);
                    (ErrorHandler::getInstance())->log(Logger::INFO, "Service occupied", __METHOD__);
                    return new TextResponse("Service occupied", 423);
                }
            }

            $startTime = microtime(true);

            $rtn = (new DocumentConverter())($files, $conf);

            (ErrorHandler::getInstance())->log(Logger::DEBUG, "Converted files in "
                . (string)(microtime(true) - $startTime) . " seconds.", $files[0]->getMd5Hash());

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
            $lock = null;
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
            $this->warnUploadFileSize();
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
            $this->warnUploadFileSize();
            throw new BadRequestException("Parameter file or files not set", 103, 400);
        }


        $files = [];
        foreach ($UploadedFiles as $UploadedFile) {
            if (null == $UploadedFile || UPLOAD_ERR_OK !== $UploadedFile->getError()) {
                if (null == $UploadedFile || $UploadedFile->getError() === 4) {
                    throw new BadRequestException('No file was uploaded', 104, 400);
                }

                if ($UploadedFile->getError() === 1) {
                    (ErrorHandler::getInstance())->log(Logger::INFO, 'upload_max_filesize exceeded' , __METHOD__);
                    throw new BadRequestException('File to large', 105, 400);
                }

                (ErrorHandler::getInstance())->log(Logger::INFO,'php file upload error coder: '. $UploadedFile->getError(), __METHOD__);
                throw new BadRequestException('File upload error ', 106, 400);
            }
            $path = (Config::getInstance())->get('tempdir') . 'upload' . uniqid()
                . basename($UploadedFile->getClientFilename());

            (ErrorHandler::getInstance())->dlog(['message' => 'Uploaded file', 'path' => $path], __METHOD__);

            try {
                $UploadedFile->moveTo($path);
            } catch (UploadedFileErrorException $exception) {
                (ErrorHandler::getInstance())->dlog([
                    'message' => 'Upload error',
                    'des-path' => $path,
                    'size'=> $UploadedFile->getSize(),
                    'error' => $UploadedFile->getError(),
                    'clientFilename' => $UploadedFile->getClientFilename(),
                    'clientMediaType' => $UploadedFile->getClientMediaType(),

                ], __METHOD__);

                throw new DocumentPreviewException("File upload error", 107, 500);
            }

            $file = new File($path, true, null, true);

            $files[] = $file;
        }
        return $files;
    }

    protected function warnUploadFileSize() {
        (ErrorHandler::getInstance())->log(Logger::WARN, "php post_max_size=" . ini_get('post_max_size') . " might be to small");
        (ErrorHandler::getInstance())->log(Logger::WARN, "php upload_max_filesize=" . ini_get('upload_max_filesize') . " might be to small");
    }

    /**
     * Check config, also done in documentConverter
     *
     * @return bool
     */
    protected function checkConfig(): bool
    {
        //todo -> but is also checked later
        return true;
    }
}
