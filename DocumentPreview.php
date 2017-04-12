<?php
class DocumentPreview
{
    public function __invoke()
    {
        // setup
        $config = new Zend\Config\Config(include 'config.php');

        $writer = new Zend\Log\Writer\Stream($config->get('logFile', 'log'));
        $logger = new Zend\Log\Logger();
        $logger->addWriter($writer);

        $rhost = $_SERVER['REMOTE_ADDR'];

        $dir = $config->get('tempDir', 'temp/');

        $downDir = $config->get('downDir', 'download/');

        $exts = $config->get('ext', array());
        if (!is_array($exts)){
            $exts = $exts->toArray();
        }

        // check post
        if (!isset($_POST["config"])) {
            $logger->info("[INFO][$rhost]Missing arguments");
            header($_SERVER["SERVER_PROTOCOL"]." 400 Bad request missing arguments");
            return;
        }
        $json = $_POST["config"];

        $conf = json_decode($json, true);
        if ($this->checkConfig($conf)) {
            $logger->info("[INFO][$rhost] JSON error");
            echo "JSON error";
            return;
        }

        // magic setup
        $path = $this->moveFile($dir, $logger);
        if ($path === -1) {
            $logger->err("[ERROR][$rhost] Failed to move uploaded File");
            echo "Internal server error";
            return;
        }

        //file check
        if (false === $this->checkExtension($path, $exts)) {
            $logger->info("[INFO][$rhost] Invalid Extension");
            echo "Invalid Extension";
            return;
        }

        //magic
        $ipcId = ftok(__FILE__, 'g');
        if ($ipcId === -1) {
            $logger->err("[ERROR][$rhost] Could not generate ftok");
            echo "Interal server error";
            return;
        }

        $semaphore = sem_get($ipcId, $config->get('maxProc', 4));
        if ($semaphore === false) {
            $logger->err("[ERROR][$rhost]Failed not get semaphore");
            echo("Internal server error");
            return;
        }

        // todo add config wait time out
        // https://bugs.php.net/bug.php?id=39168
        // find better way to do
        if (sem_acquire($semaphore) !== true) {
            $logger->info("[INFO][$rhost] Service occupied");
            echo "Service occupied";
            return;
        }

        $rtn = $this->magic($path, $downDir);
        if ($rtn == -1) {
            $logger->err("[ERROR][$rhost] Failed to generate Image");
            echo "Internal server error";
            if (!sem_release($semaphore)) {
                $logger->err("[ERROR][$rhost] Failed to release semaphore");
            }
            return;
        }

        if (!sem_release($semaphore)) {
            $logger->err("[ERROR][$rhost] Failed to release semaphore");
        }

        // retrun
        $this->returnImage($rtn);

        // clean up
        if (!unlink($path)) {
            $logger->err("[ERROR][$rhost] Failed to unlink " . $path);
        }
    }

    protected function checkConfig($conf){
        if (!is_array($conf)) {
            return true;
        }
        return false;
    }

    protected function checkExtension($path, $exts){
        $ext = pathinfo($path, FILE_EXTENTIONS);
        return in_array($ext, $exts);
    }

    protected function moveFile($dir, $logger){
        if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
            $logger->err("[ERROR] File upload error");
            return -1;
        }

        $tmp_name = $_FILES["file"]["tmp_name"];

        $path = $dir.uniqid().basename($_FILES["file"]["name"]);

        if(!move_uploaded_file($tmp_name, $path)){
            $logger->err("[ERROR] Failed to move file");
            return -1;
        }

        if (!is_file($path)){
            $logger->err("[ERROR] File was not moved");
            return -1;
        }

        return $path;
    }

    protected function magic($path, $downDir){
        $key = "e34d7363ed";
        $downDir = $downDir.$key.'/';
        return array(
            'thumbnail' => $downDir.'thumb.jpg',
            'previewSmall' => array(
                $downDir.'pres1.jpg',
                $downDir.'pres2.jpg'
            ),
            'preview' => array(
                $downDir.'pre1.jpg',
                $downDir.'pre2.jpg'
            ),
        );
    }

    protected function returnImage($rtn){
        echo json_encode($rtn);
    }
}