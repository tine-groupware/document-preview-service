<?php namespace DocumentServiceTest;

use DocumentService\DocumentConverter\Config;
use DocumentService\DocumentConverter\ImageFile;
use PHPUnit\Framework\TestCase;
use Zend\Log\Logger;

class ImageFileTest extends TestCase {

    public static function setUpBeforeClass() {
        Config::getInstance()->initialize(new \Zend\Config\Config([]));
        parent::setUpBeforeClass();
    }


    /**
     * @dataProvider dataConvertToSize
     */
    public function testConvertToSize($ext, $x, $y, $color, $mimeType) {
        $file = new ImageFile('./testFiles/imATestFile001.png');
        $images = $file->fitToSize($ext, $x, $y, $color);

        $this->assertFileExists($images->getPath());
        $this->assertEquals($mimeType, mime_content_type($images->getPath()));
    }

    public function dataConvertToSize() {
        return [
            ['png', 10, 10, false, 'image/png'],
            ['jpg', 10, 10, false, 'image/jpeg'],
            ['png', 10, 10, 'blue', 'image/png'],
            ['jpg', 10, 10, 'red', 'image/jpeg'],
            ['tiff', 1000, 1000, false, 'image/tiff'],
            ['jpg', 1000, 1000, false, 'image/jpeg'],
            ['png', 1000, 1000, 'blue', 'image/png'],
            ['tiff', 1000, 1000, 'red', 'image/tiff'],
        ];
    }
}
