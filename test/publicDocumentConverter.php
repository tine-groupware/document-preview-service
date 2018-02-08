<?php

namespace DocumentServiceTest;

use DocumentService\DocumentConverter;

class docCon extends DocumentConverter
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

    public function setLogger($value){
        $this->logger = $value;
    }

    public function setConfig($value){
        $this->config = $value;
    }



    public function _onlySingelPage($conf)
    {
        return $this->onlySingelPage($conf);
    }

    public function _convertToPDF($path, $uid)
    {
        return $this->convertToPDF($path, $uid);
    }

    public function _covertToPNG($uid, $conf, $name, $ext)
    {
        return $this->covertToPNG($uid, $conf, $name, $ext);
    }
    
    public function _convertToSize($uid, $conf, $inputFileType, $name)
    {
        return $this->convertToSize($uid, $conf, $inputFileType, $name);
    }

    public function _cleanUp($uid)
    {
        $this->cleanUp($uid);
    }

    public function _getReturn($uid, $inputFileType, $conf)
    {
        return $this->getReturn($uid, $inputFileType, $conf);
    }

    public static function _checkConfig($conf)
    {
        return self::checkConfig($conf);
    }
}