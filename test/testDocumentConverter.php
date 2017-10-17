<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/publicDocumentConverter.php';
require_once __DIR__ . '/publicDocumentPreview.php';

final class testDocumentConverter extends TestCase
{
    protected $workDir;
    protected $logger;
    protected $config;
    protected $fileDir;

    public function setup(){
        $this->config = new Zend\Config\Config(array());

        $this->fileDir = dirname(__FILE__).'/testFiles/';

        $this->workDir = dirname(__FILE__).'/test/';
        if(true === is_dir($this->workDir)) {
            exec('rm -r ' . $this->workDir);
        }
        mkdir($this->workDir);
        mkdir($this->workDir.'test1/');
        mkdir($this->workDir.'test1/temp/');
        mkdir($this->workDir.'test2/');
        mkdir($this->workDir.'test2/temp/');
        mkdir($this->workDir.'test2/temp/1248/');
        mkdir($this->workDir.'test3/');
        mkdir($this->workDir.'test3/temp/');
        mkdir($this->workDir.'test3/temp/1248/');
        mkdir($this->workDir.'test3/download/');
        mkdir($this->workDir.'test3/download/1248');
        mkdir($this->workDir.'test4/');
        mkdir($this->workDir.'test4/temp/');
        mkdir($this->workDir.'test4/download/');

        exec('cp '.$this->fileDir.'imATestFile.pdf '.$this->workDir.'test2/temp/1248/');
        exec('cp '.$this->fileDir.'*.png '.$this->workDir.'test3/temp/1248/');

        $writer = new Zend\Log\Writer\Stream($this->workDir . 'log');
        $this->logger = new Zend\Log\Logger();
        $this->logger->addWriter($writer);
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
        $docCon = new docCon($this->workDir.'test1/temp/', $this->workDir.'test1/download/', 'test.com', $this->logger, $this->config);
        $docCon->_convertToPDF($this->fileDir.'imATestFile.odt', '1248');
        $this->assertTrue(is_file($this->workDir.'test1/temp/1248/imATestFile.pdf'));
    }

    public function testCovertToPNG(){
        $docCon = new docCon($this->workDir.'test2/temp/', $this->workDir.'test2/download/', 'test.com', $this->logger, $this->config);
        $docCon->_covertToPNG('1248', ['t1' => ['firstPage' => false]], 'imATestFile');
        $this->assertTrue(is_file($this->workDir.'test2/temp/1248/imATestFile001.png') && is_file($this->workDir.'test2/temp/1248/imATestFile002.png') && is_file($this->workDir.'test2/temp/1248/imATestFile003.png') && is_file($this->workDir.'test2/temp/1248/imATestFile004.png') && is_file($this->workDir.'test2/temp/1248/imATestFile005.png'));
    }

    public function testConvertToSize(){
        $docCon = new docCon($this->workDir.'test3/temp/', $this->workDir.'test3/download/', 'test.com', $this->logger, $this->config);
        $docCon->_convertToSize('1248', ['Key' => ['filetype' => 'jpg', 'firstPage' => false, 'x' => 50, 'y' => 70, 'color' => 'blue'], 'Yek' => ['filetype' => 'gif', 'firstPage' => true, 'x' => 100, 'y' => 190, 'color' => false]], 'png', 'imATestFile');
        $this->assertTrue(is_file($this->workDir.'test3/download/1248/Key-000.jpg') && is_file($this->workDir.'test3/download/1248/Key-001.jpg') && is_file($this->workDir.'test3/download/1248/Key-002.jpg') && is_file($this->workDir.'test3/download/1248/Key-003.jpg') && is_file($this->workDir.'test3/download/1248/Key-004.jpg') && is_file($this->workDir.'test3/download/1248/Yek.gif'));
    }

    public function testCheckConfig(){
        $this->assertEquals(true, docCon::_checkConfig(['Key' => ['filetype' => 'jpg', 'firstPage' => false, 'x' => 50, 'y' => 70, 'color' => 'blue'], 'Yek' => ['filetype' => 'gif', 'firstPage' => true, 'x' => 100, 'y' => 190, 'color' => false]]));
        $this->assertEquals(false, docCon::_checkConfig(['Key' => ['filetype' => 'jpg', 'firstpage' => false, 'x' => 50, 'y' => 70, 'color' => 'blue'], 'Yek' => ['filetype' => 'gif', 'firstPage' => true, 'x' => 100, 'y' => 190, 'color' => false]]));
        $this->assertEquals(false, docCon::_checkConfig([]));


    }

    /**
     * @dataProvider dataInvoke
     */
    public function testInvoke($name, $conf, $uid, $expected)
    {
        exec('cp '.$this->fileDir.$name.' '.$this->workDir.'test4/temp/');
        $docConverter = new DocumentConverter($this->workDir.'test4/temp/', $this->workDir.'test4/download/', 'test.com', $this->logger, $this->config);
        $docConverter($this->workDir.'test4/temp/'.$name, $uid, json_decode($conf, true));
        foreach ($expected as $exp){
            $this->assertFileExists($this->workDir.$exp, $this->workDir.$exp);
        }
    }

    public function dataInvoke()
    {
        return [
            [   'imATestFile.odt',
                '{"Key":{"filetype":"jpg","firstPage":false,"x":50,"y":70,"color":"blue"}}',
                'U1',
                [   $this->workDir.'test4/download/U1/Key-000.jpg',
                    $this->workDir.'test4/download/U1/Key-001.jpg',
                    $this->workDir.'test4/download/U1/Key-002.jpg',
                    $this->workDir.'test4/download/U1/Key-003.jpg',
                    $this->workDir.'test4/download/U1/Key-004.jpg'
                ]
            ],
            [   'imATestFile.odt',
                '{"Key":{"filetype":"jpg","firstPage":false,"x":50,"y":70,"color":"blue"},"Yek":{"filetype":"png","firstPage":true,"x":100,"y":190,"color":false}}',
                'U2',
                [   $this->workDir.'test4/download/U2/Key-000.jpg',
                    $this->workDir.'test4/download/U2/Key-001.jpg',
                    $this->workDir.'test4/download/U2/Key-002.jpg',
                    $this->workDir.'test4/download/U2/Key-003.jpg',
                    $this->workDir.'test4/download/U2/Key-004.jpg',
                    $this->workDir.'test4/download/U2/Yek.png'
                ]
            ],
            [   'imATestFile.pdf',
                '{"Key":{"filetype":"jpg","firstPage":false,"x":50,"y":70,"color":"blue"}}',
                'U3',
                [   $this->workDir.'test4/download/U3/Key-000.jpg',
                    $this->workDir.'test4/download/U3/Key-001.jpg',
                    $this->workDir.'test4/download/U3/Key-002.jpg',
                    $this->workDir.'test4/download/U3/Key-003.jpg',
                    $this->workDir.'test4/download/U3/Key-004.jpg'
                ]
            ],
            [   'imATestFile.pdf',
                '{"Key":{"filetype":"jpg","firstPage":false,"x":50,"y":70,"color":"blue"},"Yek":{"filetype":"png","firstPage":true,"x":100,"y":190,"color":false}}',
                'U4',
                [   $this->workDir.'test4/download/U4/Key-000.jpg',
                    $this->workDir.'test4/download/U4/Key-001.jpg',
                    $this->workDir.'test4/download/U4/Key-002.jpg',
                    $this->workDir.'test4/download/U4/Key-003.jpg',
                    $this->workDir.'test4/download/U4/Key-004.jpg',
                    $this->workDir.'test4/download/U4/Yek.png'
                ]
            ],
            [   'imATestFile001.png',
                '{"Key":{"filetype":"jpg","firstPage":false,"x":50,"y":70,"color":"blue"}}',
                'U5',
                [   $this->workDir.'test4/download/U5/Key-000.jpg',
                ]
            ],
        ];
    }
}
