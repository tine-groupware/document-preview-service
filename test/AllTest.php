<?php

use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\TestSuite;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/testDocumentPreview.php';
require_once __DIR__ . '/testDocumentConverter.php';

class AllTest
{
    public static function main()
    {
        TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new TestSuite('All document preview tests');

        $suite->addTestSuite(testDocumentPreview::class);
        $suite->addTestSuite(testDocumentConverter::class);
        return $suite;
    }
}