<?php namespace DocumentServiceTest;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\PdfFile;
use PHPUnit\Framework\TestCase;
use Zend\Log\Logger;

class PdfFileTest extends TestCase {

    public static function setUpBeforeClass() {
        Config::getInstance()->initialize(new Logger(), new \Zend\Config\Config([]));
        parent::setUpBeforeClass();
    }

    public function testConvertToNext() {
        $file = new PdfFile('./testFiles/imATestFile.pdf');
        $pngs = $file->convertToPng();

        foreach ($pngs as $png) {
            $this->assertFileExists($png->getPath());
            $this->assertEquals('image/png', mime_content_type($png->getPath()));
        }
    }

    public function testMerge() {
        $pdfs = [];
        for ($i = 1; $i < 4; $i++) {
            $pdfs[] = new PdfFile('./testFiles/imATestFile.pdf');
            $pdf = PdfFile::merge([new PdfFile('./testFiles/imATestFile.pdf'), new PdfFile('./testFiles/imATestFile.pdf')]);
            $this->assertFileExists($pdf->getPath());
            $this->assertEquals('application/pdf', mime_content_type($pdf->getPath()));
        }
    }
}
