<?php

namespace DocumentServiceTest;

require_once __DIR__.'/publicDocumentPreview.php';

use DocumentServiceTest\docPre;
use PHPUnit\Framework\TestCase;

final class testDocumentPreview extends TestCase
{

    //todo process test

    /**
     * @dataProvider dataCheckExtension
     */
    public function testCheckExtension($path, $exts, $expected)
    {
        $docPre = new docPre([]);
        $this->assertEquals($expected, $docPre->_checkExtension($path, $exts));
    }

    public function dataCheckExtension()
    {
        return [
            ["/test/imAZip.zip", ["doc", "txt", "odt"], false],
            ["/test/imAFile.doc", ["doc", "txt", "odt"], true],
            ["/test/imAfile.doc", ["zip", "png", "jpg"], false],
            ["/test/goto/units.xls", ["test", "xls", "go"], true],
            ["imAFile.odt", ["odt", "ods", "pgt"], true],
        ];
    }

    /**
     * @dataProvider dataSemAcquireBlock
     */
    public function testSemAcquireBlock($maxProc, $expected)
    {
        $docPre = new docPre([]);

        $docPre->setSemTimeOut(1);

        $ipcId = ftok(__FILE__, 'g');

        $semaphore = sem_get($ipcId, $maxProc);

        $semAcq = $docPre->_semAcquire($semaphore);

        if(true === $semAcq){
            sem_release($semaphore);
        }

        $this->assertEquals($expected, $semAcq);
    }

    public function dataSemAcquireBlock(){
        return [
            [1, true],
            [0, false]
        ];
    }

    /**
     * @dataProvider dataSemAcquireTimeOut
     */
    public function testSemAcquireTimeOut($timeOut)
    {
        $docPre = new docPre([]);

        $docPre->setSemTimeOut($timeOut);

        $ipcId = ftok(__FILE__, 'g');

        $semaphore = sem_get($ipcId, 0);

        $timeAtStart = time();

        $semAcq = $docPre->_semAcquire($semaphore);

        if(true === $semAcq){
            sem_release($semaphore);
        }
        $x = time() - $timeAtStart;

        $this->assertTrue(($timeOut -1 < $x && $x < $timeOut + 1));
    }

    public function dataSemAcquireTimeOut(){
        return[[10], [5], [30]];
    }

    /**
     * @dataProvider dataCheckConfig
     */
    public function testCheckConfig($data, $expected){
        $docPre = new docPre([]);
        $this->assertEquals($expected, $docPre->_checkConfig($data));
    }

    public function dataCheckConfig(){
        return [
            [$this, false],
            [array(), true],
            [[], true],
            [array('test'), true],
            [array('hello','test'),true],
            [10, false],
            ['{"test":[1,2,3],"tester":{"testing":true}', false]
        ];
    }
}