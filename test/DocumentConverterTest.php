<?php namespace DocumentServiceTest;

use DocumentService\DocumentConverter;
use PHPUnit\Framework\TestCase;
use Zend\Log\Logger;

class DocumentConverterTest extends TestCase {

    public static function setUpBeforeClass(){
        var_dump(getcwd());
        chdir('./test');
        mkdir('tmp');
    }

    /**
     * @dataProvider dataGetExtType
     */
    public function testGetExtType($paths, $extType) {
        $result = (new DocumentConverter('', new Logger(), new \Zend\Config\Config(['tempDir' => './tmp/'])))->getExtType($paths);
        $this->assertEquals($extType, $result);
    }

    public function dataGetExtType(){
        return [
            [['./testFiles/imATestFile.odt', './testFiles/structured/test.doc', './testFiles/structured/test.docx', './testFiles/structured/test.xls'], 1],
            [['./testFiles/imATestFile.pdf', './testFiles/structured/test.pdf', './testFiles/structured/test.pdf', './testFiles/imATestFile.Pdf'], 2],
            [['./testFiles/imATestFile.png', './testFiles/structured/test.tiff', './testFiles/structured/test.jpg', './testFiles/imATestFile.PNG'], 4],
        ];
    }

    /**
     * @dataProvider dataCleanConf
     */
    public function testCleanConf($conf, $actual) {
        $result = (new DocumentConverter('', new Logger(), new \Zend\Config\Config([])))->cleanConf($conf);
        $this->assertEquals($actual, $result);
    }


    public function dataCleanConf() {
        return [
            [['k1'=> ['firstPage' => false, 'fileType' => 'png', 'x' => 200, 'y' => 200, 'color' => false, 'merge' => true,]],
             ['k1'=> ['firstPage' => false, 'fileType' => 'png', 'x' => 200, 'y' => 200, 'color' => false, 'merge' => true,]]],

            [['k1'=> ['firstPage' => true, 'fileType' => 'pdf', 'x' => 500, 'y' => 1, 'color' => 'blue', 'merge' => false,]],
             ['k1'=> ['firstPage' => true, 'fileType' => 'pdf', 'x' => 500, 'y' => 1, 'color' => 'blue', 'merge' => false,]]],

            [['k1'=> ['firstPage' => false, 'fileType' => 'JPG', 'x' => 500, 'y' => 100, 'color' => 'false', 'merge' => 'false',]],
             ['k1'=> ['firstPage' => false, 'fileType' => 'jpg', 'x' => 500, 'y' => 100, 'color' => false, 'merge' => false,]]],

            [['k1'=> ['firstpage' => false, 'filetype' => 'JPG', 'x' => 500, 'y' => 1, 'color' => false, 'merge' => false,]],
             ['k1'=> ['firstPage' => false, 'fileType' => 'jpg', 'x' => 500, 'y' => 1, 'color' => false, 'merge' => false,]]],

            [['k1'=> ['firstpage' => false, 'filetype' => 'PDF', 'x' => 500, 'y' => 1, 'color' => false, 'merge' => false,'path' => "/dev/null"]],
             ['k1'=> ['firstPage' => false, 'fileType' => 'pdf', 'x' => 500, 'y' => 1, 'color' => false, 'merge' => false,]]],
        ];
    }

    /**
     * @dataProvider dataInvoke
     */
    public function testInvoke($paths, $conf, $lengths) {
        $result = (new DocumentConverter('', new Logger(), new \Zend\Config\Config([])))($paths, $conf);
        foreach ($lengths as $key => $length) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals($length, count($result[$key]), $key);
        }
    }

    public function dataInvoke() {
        return [
            [['./testFiles/imATestFile.odt'], [
            'k1' => ['firstPage' => false, 'fileType' => 'jpg', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k2' => ['firstPage' => false, 'fileType' => 'pdf', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k3' => ['firstPage' => false, 'fileType' => 'odt', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k4' => ['firstPage' => true, 'fileType' => 'png', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k5' => ['firstPage' => true, 'fileType' => 'jpg', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,]],
                ['k1' => 5, 'k2' => 1, 'k3' => 1, 'k4' => 1, 'k5' => 1]],
            [['./testFiles/imATestFile.pdf'], [
                'k1' => ['firstPage' => false, 'fileType' => 'jpg', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k2' => ['firstPage' => false, 'fileType' => 'pdf', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k4' => ['firstPage' => true, 'fileType' => 'png', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k5' => ['firstPage' => true, 'fileType' => 'jpg', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,]],
                ['k1' => 5, 'k2' => 1, 'k4' => 1, 'k5' => 1]],
            [['./testFiles/imATestFile.pdf', './testFiles/imATestFile.Pdf'], [
                'k1' => ['firstPage' => false, 'fileType' => 'pdf', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k2' => ['firstPage' => false, 'fileType' => 'pdf', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => false,],
                'k3' => ['firstPage' => true, 'fileType' => 'png', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k4' => ['firstPage' => false, 'fileType' => 'png', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,]],
                ['k1' => 1, 'k2' => 2, 'k3' => 1, 'k4' => 10,]],
            [['./testFiles/imATestFile.odt', './testFiles/imATestFile.odt'], [
                'k1' => ['firstPage' => false, 'fileType' => 'pdf', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k2' => ['firstPage' => false, 'fileType' => 'pdf', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => false,],
                'k3' => ['firstPage' => true, 'fileType' => 'png', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,],
                'k4' => ['firstPage' => false, 'fileType' => 'png', 'x' => 10, 'y' => 10, 'color' => false, 'merge' => true,]],
                ['k1' => 1, 'k2' => 2, 'k3' => 1, 'k4' => 10,]],
        ];
    }
}