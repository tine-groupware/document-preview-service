<?php namespace DocumentServiceTest;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\Converter\DocumentToPdf;
use DocumentService\DocumentConverter\DocumentFile;
use DocumentService\DocumentConverter\FileSystem\File;
use DocumentService\DocumentConverter\Request;
use PHPUnit\Framework\TestCase;
use Zend\Log\Logger;

class DocumentFileTest extends TestCase
{

    public static function setUpBeforeClass()
    {
        Config::getInstance()->initialize(new \Zend\Config\Config([]));
        parent::setUpBeforeClass();
        chdir('test');
    }

    public function testConvertToNext()
    {
        $con = new DocumentToPdf();
        $file = new File('./testFiles/imATestFile.odt');
        $request = new Request();
        $request->fileTypes = ['pdf'];
        $request->fileType = 'pdf';

        $pdf = $con->convert($file, $request)[0];


        $this->assertFileExists($pdf->getPath());
        $this->assertEquals('application/pdf', mime_content_type($pdf->getPath()));
    }
}
