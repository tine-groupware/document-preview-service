<?php

use PHPUnit\Framework\TestCase;

require '../vendor/autoload.php';

final class testDocumentPreview extends TestCase
{
    protected $workDir;
    protected $logger;
    protected $config;
    protected $fileDir;

    public function setup(){
        $this->config = new Zend\Config\Config(array());
        $writer = new Zend\Log\Writer\Stream($this->config->get('logFile', '/dev/zero'));
        $this->logger = new Zend\Log\Logger();
        $this->logger->addWriter($writer);

        $this->fileDir = dirname(__FILE__).'/testFiles/';

        $this->workDir = dirname(__FILE__).'/test/';
        if(true === is_dir($this->workDir)) {
            exec('rm -r ' . $this->workDir);
        }
        mkdir($this->workDir);
    }

    public function tearDown()
    {
        exec('rm -r ' . $this->workDir);
        parent::tearDown();
    }

    /**
     * @dataProvider dataOnlySingelPage
     */
    public function testOnlySingelPage($data, $expected){
        $docCon = new docCon($this->workDir.'test0/temp/', $this->workDir.'test0/download/', 'test.com', $this->logger, $this->config);
        $this->assertEquals($expected, $docCon->_onlySingelPage($data));

    }

    public function dataOnlySingelPage(){
        return [
            [['t0' => ['firstPage' => true]],true],
            [['t1' => ['firstPage' => false]],false],
            [['t20' => ['firstPage' => true],'t21' => ['firstPage' => true]],true],
            [['t30' => ['firstPage' => true],'t31' => ['firstPage' => false]],false]
        ];
    }

    public function testConvertToPDF(){
        mkdir($this->workDir.'test1/');
        mkdir($this->workDir.'test1/temp/');
        $docCon = new docCon($this->workDir.'test1/temp/', $this->workDir.'test1/download/', 'test.com', $this->logger, $this->config);
        $docCon->_convertToPDF($this->fileDir.'imATestFile.odt', '1248');
        $this->assertTrue(is_file($this->workDir.'test1/temp/1248/imATestFile.pdf'));
        exec('rm -r ' . $this->workDir.'test1/');
    }

    public function testCovertToPNG(){
        mkdir($this->workDir.'test2/');
        mkdir($this->workDir.'test2/temp/');
        mkdir($this->workDir.'test2/temp/1248/');
        $docCon = new docCon($this->workDir.'test2/temp/', $this->workDir.'test2/download/', 'test.com', $this->logger, $this->config);
        exec('cp '.$this->fileDir.'imATestFile.pdf '.$this->workDir.'test2/temp/1248/');
        $docCon->_covertToPNG('1248', ['t1' => ['firstPage' => false]], 'imATestFile');
        $this->assertTrue(is_file($this->workDir.'test2/temp/1248/imATestFile001.png') && is_file($this->workDir.'test2/temp/1248/imATestFile002.png') && is_file($this->workDir.'test2/temp/1248/imATestFile003.png') && is_file($this->workDir.'test2/temp/1248/imATestFile004.png') && is_file($this->workDir.'test2/temp/1248/imATestFile005.png'));
        exec('rm -r ' . $this->workDir.'test2/');
    }

    public function testConvertToSize(){
        mkdir($this->workDir.'test3/');
        mkdir($this->workDir.'test3/temp/');
        mkdir($this->workDir.'test3/temp/1248/');
        mkdir($this->workDir.'test3/download/');
        mkdir($this->workDir.'test3/download/1248');
        exec('cp '.$this->fileDir.'*.png '.$this->workDir.'test3/temp/1248/');
        $docCon = new docCon($this->workDir.'test3/temp/', $this->workDir.'test3/download/', 'test.com', $this->logger, $this->config);
        $docCon->_convertToSize('1248', ['Key' => ['filetype' => 'jpg', 'firstPage' => false, 'x' => 50, 'y' => 70, 'color' => 'blue'], 'Yek' => ['filetype' => 'gif', 'firstPage' => true, 'x' => 100, 'y' => 190, 'color' => false]], 'png', 'imATestFile');
        $this->assertTrue(is_file($this->workDir.'test3/download/1248/Key-0.jpg') && is_file($this->workDir.'test3/download/1248/Key-1.jpg') && is_file($this->workDir.'test3/download/1248/Key-2.jpg') && is_file($this->workDir.'test3/download/1248/Key-3.jpg') && is_file($this->workDir.'test3/download/1248/Key-4.jpg') && is_file($this->workDir.'test3/download/1248/Yek.gif'));
        exec('rm -r ' . $this->workDir.'test3/');
    }

    public function testCheckConfig(){
        $this->assertEquals(true, docCon::_checkConfig(['Key' => ['filetype' => 'jpg', 'firstPage' => false, 'x' => 50, 'y' => 70, 'color' => 'blue'], 'Yek' => ['filetype' => 'gif', 'firstPage' => true, 'x' => 100, 'y' => 190, 'color' => false]]));
        $this->assertEquals(false, docCon::_checkConfig([]));
    }
}