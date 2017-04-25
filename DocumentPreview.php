<?php
class DocumentPreview
{
    protected $config;
    protected $logger;
    protected $tempDir;
    protected $downDir;
    protected $downUrl;
    protected $semTimeOut;
    protected $maxProc;

    /** @codeCoverageIgnore */
    public function __construct($configFile)
    {
        if('' === $configFile){
            $this->config = new Zend\Config\Config(array());
        } else {
            $this->config = new Zend\Config\Config(include($configFile));
        }

        $writer = new Zend\Log\Writer\Stream($this->config->get('logFile', '/dev/zero'));
        $this->logger = new Zend\Log\Logger();
        $this->logger->addWriter($writer);

        $this->tempDir = $this->config->get('tempDir', 'temp/');
        $this->downDir = $this->config->get('downDir', 'download/');
        $this->downUrl = $this->config->get('downUrl', 'download/');
        $this->semTimeOut = $this->config->get('timeOut', 30);
        $this->maxProc = $this->config->get('maxProc', 4);
    }

    /** @codeCoverageIgnore */
    public function __invoke()
    {
        // setup
        $rhost = $_SERVER['REMOTE_ADDR'];

        $exts = $this->config->get('ext', array());
        if (false === is_array($exts)){
            $exts = $exts->toArray();
        }

        // check post
        if (false === isset($_POST["config"])) {
            $this->logger->info(__METHOD__ . ' ' . __LINE__ . ': ' . "[INFO][$rhost]Missing arguments");
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad request missing arguments");
            return;
        }
        $json = $_POST["config"];

        $conf = json_decode($json, true);
        if ( false === $this->checkConfig($conf, true)) {
            $this->logger->info(__METHOD__ . ' ' . __LINE__ . ': ' . "[INFO][$rhost] JSON error");
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad request JSON error");
            return;
        }


        // magic setup
        $path = $this->moveFile();
        if ( -1 === $path) {
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Failed to move uploaded File");
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal server error");
            return;
        }

        //file check
        if (false === $this->checkExtension($path, $exts)) {
            $this->logger->info(__METHOD__ . ' ' . __LINE__ . ': ' . "[INFO][$rhost] Invalid Extension");
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad request Invalid Extension");
            return;
        }

        //magic
        $ipcId = ftok(__FILE__, 'g');
        if (-1 === $ipcId) {
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Could not generate ftok");
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal server error");
            return;
        }

        $semaphore = sem_get($ipcId, $this->maxProc);
        if (false === $semaphore) {
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost]Failed not get semaphore");
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal server error");
            return;
        }

        $rtn = NULL;

        try {
            $semAcq = $this->semAcquire($semaphore);
            if (false === $semAcq) {
                $this->logger->info(__METHOD__ . ' ' . __LINE__ . ': ' . "[INFO][$rhost] Service occupied");
                echo '';//todo
                return;
            }

            $rtn = $this->magic($path, $conf);
            if (false === $rtn) {
                $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Failed to generate Images");
                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal server error");
                return;
            }

        } finally {
            if (null !== $semaphore && true === $semAcq) {
                if (false === sem_release($semaphore)) {
                    $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Failed to release semaphore");
                }
            }
        }

        // retrun
        $this->returnImage($rtn);

        // clean up
        if (false === unlink($path)) {
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR][$rhost] Failed to unlink " . $path);
        }
    }

    // check config

    protected function checkExtension($path, $exts){
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($ext, $exts);
    }

    /** @codeCoverageIgnore */
    protected function moveFile(){
        if (UPLOAD_ERR_OK !== $_FILES["file"]["error"]) {
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] File upload error");
            return -1;
        }

        $tmp_name = $_FILES["file"]["tmp_name"];

        $path = $this->tempDir.uniqid().basename($_FILES["file"]["name"]);

        if (false === move_uploaded_file($tmp_name, $path)){
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] Failed to move file");
            return -1;
        }

        if (false === is_file($path)){
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . "[ERROR] File was not moved");
            return -1;
        }

        return $path;
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

    /** @codeCoverageIgnore */
    protected function magic($path, $conf){
        $uid = uniqid();
        $docConverter = new DocumentConverter($this->tempDir, $this->downDir, $this->downUrl, $this->logger, $this->config);
        return $docConverter($path, $uid, $conf);
    }

    protected function returnImage($rtn){
        echo json_encode($rtn);
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

/*
POST
+ file = fileToConvert;
+ config = {
    "Key(N)":[
    'firstPage': (true||false),
    'filetype' : '(image e.g. png||jpg)',
    'x' : (size in px),
    'y' : (size in px),
    'color' : '(color e.g. white||blue)' || 'false',
    ],
    ...
}

JSON return {
    "Key(N)":[
    '(link to image)',
    ...
    ],

    ||

    "Key(N)": '(link to image)',
    ...
}
*/

/*
DocumentConverter:
__construct($tempDir, $downDir, $downUrl)
__invoke($path, $uid, $conf)
static checkConfig($conf)
 */