<?php

namespace DocumentServiceTest;

require_once __DIR__.'/publicDocumentPreview.php';

use DocumentService\DocumentConverter\Config;
use DocumentServiceTest\docPre;
use PHPUnit\Framework\TestCase;

final class testDocumentPreview extends TestCase
{
    public static function setUpBeforeClass(){
        (Config::getInstance())->initialize(new \Zend\Config\Config([]));
    }

    /**
     * @dataProvider dataSemAcquireBlock
     */
    public function testSemAcquireBlock($maxProc, $expected)
    {
        $docPre = new docPre(['tempDir' => '/tmp']);

        $docPre->setSemTimeOut(1);

        $ipcId = ftok(__FILE__, 'g');

        $semaphore = sem_get($ipcId, $maxProc);

        $semAcq = $docPre->_semAcquire($semaphore);

        if (true === $semAcq) {
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
        $docPre = new docPre(['timeOut' => $timeOut, 'tempDir' => '/tmp']);

        $ipcId = ftok(__FILE__, 'g');

        $semaphore = sem_get($ipcId, 0);

        $timeAtStart = time();

        $semAcq = $docPre->_semAcquire($semaphore);

        if (true === $semAcq) {
            sem_release($semaphore);
        }
        $x = time() - $timeAtStart;

        $this->assertTrue(($timeOut -1 < $x && $x < $timeOut + 1));
    }

    public function dataSemAcquireTimeOut(){
        return[[10], [5], [30]];
    }
}