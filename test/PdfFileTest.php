<?php namespace DocumentServiceTest;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\Converter\PdfToImage;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentConverter\Finalizer\PdfMergeFinalizer;
use DocumentService\DocumentConverter\PdfFile;
use DocumentService\DocumentConverter\Request;
use PHPUnit\Framework\TestCase;
use Zend\Log\Logger;

class PdfFileTest extends TestCase {

    public static function setUpBeforeClass()
    {
        Config::getInstance()->initialize(new \Zend\Config\Config([]));
        parent::setUpBeforeClass();
    }

    public function testConvertToNext()
    {
        $con = new PdfToImage();
        $file = new File('./testFiles/imATestFile.pdf');
        $request = new Request();
        $request->fileTypes = ['png'];
        $request->fileType = 'pdf';

        $pngs = $con->convert($file, $request);

        foreach ($pngs as $png) {
            $this->assertFileExists($png->getPath());
            $this->assertEquals('image/png', mime_content_type($png->getPath()));
        }
    }

    public function testMerge()
    {
        $fin = new PdfMergeFinalizer();
        $request = new Request();
        $request->fileTypes = ['pdf'];
        $request->fileType = 'pdf';
        $request->merge = true;
        $pdfs = [];

        for ($i = 1; $i < 4; $i++) {
            $pdfs[] = new File('./testFiles/imATestFile.pdf');

            $pdfFiles = $fin->convert($pdfs, $request);
            $this->assertEquals(1, sizeof($pdfFiles));

            $pdf = $pdfFiles[0];
            $this->assertFileExists($pdf->getPath());
            $this->assertEquals('application/pdf', mime_content_type($pdf->getPath()));
        }
    }
}
