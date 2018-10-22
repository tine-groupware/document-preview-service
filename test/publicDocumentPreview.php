<?php

namespace DocumentServiceTest;

use DocumentService\Action\DocumentPreview;

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

    public function _semAcquire($semaphore){ //-
        return $this->semAcquire($semaphore);
    }
}