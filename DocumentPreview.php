<?php
class DocumentPreview
{
    public function __invoke($configFile)
    {
        // setup
        $config = new Zend\Config\Config(include($configFile));

        $writer = new Zend\Log\Writer\Stream($config->get('logFile', 'log'));
        $logger = new Zend\Log\Logger();
        $logger->addWriter($writer);

        $rhost = $_SERVER['REMOTE_ADDR'];

        $tempDir = $config->get('tempDir', 'temp/');

        $downDir = $config->get('downDir', 'download/');

        $downUrl = $config->get('downUrl', 'download/');

        $exts = $config->get('ext', array());
        if (false === is_array($exts)){
            $exts = $exts->toArray();
        }

        // check post
        if (false === isset($_POST["config"])) {
            $logger->info("[INFO][$rhost]Missing arguments");
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad request missing arguments");
            return;
        }
        $json = $_POST["config"];

        $conf = json_decode($json, true);
        if ( false === $this->checkConfig($conf)) {
            $logger->info("[INFO][$rhost] JSON error");
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad request JSON error");
            return;
        }


        // magic setup
        $path = $this->moveFile($tempDir, $logger);
        if ( -1 === $path) {
            $logger->err("[ERROR][$rhost] Failed to move uploaded File");
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal server error");
            return;
        }

        //file check
        if (false === $this->checkExtension($path, $exts)) {
            $logger->info("[INFO][$rhost] Invalid Extension");
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad request Invalid Extension");
            return;
        }

        //magic
        $ipcId = ftok(__FILE__, 'g');
        if (-1 === $ipcId) {
            $logger->err("[ERROR][$rhost] Could not generate ftok");
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal server error");
            return;
        }

        $semaphore = sem_get($ipcId, $config->get('maxProc', 4));
        if (false === $semaphore) {
            $logger->err("[ERROR][$rhost]Failed not get semaphore");
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal server error");
            return;
        }

        try {
            $semAcq = $this->semAcquire($semaphore, $config);
            if (false === $semAcq) {
                $logger->info("[INFO][$rhost] Service occupied");
                echo "Service occupied";
                return;
            }

            $rtn = $this->magic($path, $conf, $tempDir, $downDir, $downUrl, $logger);
            if (false === $rtn) {
                $logger->err("[ERROR][$rhost] Failed to generate Images");
                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal server error");
                return;
            }

        } finally {
            if (null !== $semaphore && true === $semAcq) {
                if (false === sem_release($semaphore)) {
                    $logger->err("[ERROR][$rhost] Failed to release semaphore");
                }
            }
        }

        // retrun
        $this->returnImage($rtn);

        // clean up
        if (false === unlink($path)) {
            $logger->err("[ERROR][$rhost] Failed to unlink " . $path);
        }
    }

    // check config

    protected function checkExtension($path, $exts){
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($ext, $exts);
    }

    protected function moveFile($tempDir, $logger){
        if (UPLOAD_ERR_OK !== $_FILES["file"]["error"]) {
            $logger->err("[ERROR] File upload error");
            return -1;
        }

        $tmp_name = $_FILES["file"]["tmp_name"];

        $path = $tempDir.uniqid().basename($_FILES["file"]["name"]);

        if(false === move_uploaded_file($tmp_name, $path)){
            $logger->err("[ERROR] Failed to move file");
            return -1;
        }

        if (false === is_file($path)){
            $logger->err("[ERROR] File was not moved");
            return -1;
        }

        return $path;
    }

    protected function semAcquire($semaphore, $config){
        $timeStarted = time();
        do {
            $semAcq = sem_acquire($semaphore, true);
            usleep(10000);
        } while (false === $semAcq && time() - $timeStarted < $config->get('timeOut', 30));
        return $semAcq;
    }

    protected function magic($path, $conf, $tempDir, $downDir, $downUrl, $logger){
        $uid = uniqid();
        $docConverter = new DocumentConverter($tempDir, $downDir, $downUrl, $logger);
        return $docConverter($path, $uid, $conf);
    }

    protected function returnImage($rtn){
        echo json_encode($rtn);
    }

    protected function checkConfig($conf){
        if (false === is_array($conf)) {
            return false;
        }

        return DocumentConverter::checkConfig($conf);
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