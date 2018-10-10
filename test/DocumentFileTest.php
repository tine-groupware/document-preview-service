<?php namespace DocumentServiceTest;

require_once('../vendor/autoload.php');

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\DocumentFile;
use PHPUnit\Framework\TestCase;
use Zend\Log\Logger;

class DocumentFileTest extends TestCase {

    public static function setUpBeforeClass() {
        Config::getInstance()->initialize(new Logger(), new \Zend\Config\Config([]));
        parent::setUpBeforeClass();
    }

    public function testConvertToNext() {
        $file = new DocumentFile('./testFiles/imATestFile.odt');
        $pdf = $file->convertToPdf();

        $this->assertFileExists($pdf->getPath());
        $this->assertEquals('application/pdf', mime_content_type($pdf->getPath()));
    }
}
