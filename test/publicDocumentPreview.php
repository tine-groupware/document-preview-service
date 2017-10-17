<?php

require_once __DIR__ . '/../vendor/autoload.php';

class docPre extends DocumentPreview
{
    public function setTempDir($value){
        $this->tempDir = $value;
    }

    public function setDownDir($value){
        $this->downDir = $value;
    }

    public function setDownUrl($value){
        $this->downUrl = $value;
    }

    public function setSemTimeOut($value){
        $this->semTimeOut = $value;
    }

    public function setLogger($value){
        $this->logger = $value;
    }

    public function setConfig($value){
        $this->config = $value;
    }

    public function setMaxProc($value){
        $this->maxProc = $value;
    }



    public function _checkExtension($path, $exts){  //-
        return $this->checkExtension($path, $exts);
    }

    public function _moveFile(){ //?
        return $this->moveFile();
    }

    public function _semAcquire($semaphore){ //-
        return $this->semAcquire($semaphore);
    }

    public function _magic($path, $conf){//don't test
        return $this->magic($path, $conf);
    }

    public function _returnImage($rtn){ //-
        $this->returnImage($rtn);
    }

    public function _checkConfig($conf){//-
        return $this->checkConfig($conf, false);
    }
}