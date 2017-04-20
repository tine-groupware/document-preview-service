<?php

require '../vendor/autoload.php';

class test
{
    public function gtestInvoke($name, $conf, $uid, $expected)
    {
        $config = new Zend\Config\Config(array());

        $writer = new Zend\Log\Writer\Stream('/dev/zero');
        $logger = new Zend\Log\Logger();
        $logger->addWriter($writer);


        exec('cp ' . dirname(__FILE__).'/testFiles/' . $name . ' ' . dirname(__FILE__).'/test/'. 'test4/temp/');
        $docConverter = new DocumentConverter(dirname(__FILE__).'/test/' . 'test4/temp/', dirname(__FILE__).'/test/'. 'test4/download/', 'test.com', $logger, $config);
        $docConverter(dirname(__FILE__).'/test/' . 'test4/temp/' . $name, $uid, json_decode($conf, true));
        foreach ($expected as $exp) {
            if (false === is_dir($exp)) {
                return false;
            }
        }
        return true;
    }
}

$foo = new test();
$foo->gtestInvoke('imATestFile.odt','{"Key":{"filetype":"jpg","firstPage":false,"x":50,"y":70,"color":"blue"}}', 'U1',[
    dirname(__FILE__).'/test/'.'test4/download/U1/Key-0.jpg',
    dirname(__FILE__).'/test/'.'test4/download/U1/Key-1.jpg',
    dirname(__FILE__).'/test/'.'test4/download/U1/Key-2.jpg',
    dirname(__FILE__).'/test/'.'test4/download/U1/Key-3.jpg',
    dirname(__FILE__).'/test/'.'test4/download/U1/Key-4.jpg'
]);
