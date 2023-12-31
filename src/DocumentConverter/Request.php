<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\ErrorHandler;
use Zend\Log\Logger;

class Request
{
    public $firstPage = false;
    public $fileTypes = ['png'];
    public $fileType;
    public $x = 200;
    public $y = 200;
    public $color = false;
    public $merge = true;

    public static function newRequests(array $requestConfig)
    {
        $requests = [];
        foreach ($requestConfig as $key => $conf) {
            if (is_array($conf) != true) {
                if ($key != "synchronRequest") {
                    ErrorHandler::getInstance()->log(Logger::NOTICE, "Unknown array option: " . $key ." => " . $conf);
                }
                continue;
            }

            $request = new Request();

            $request->firstPage = (
                   array_key_exists('firstPage', $conf)
                && isset($conf['firstPage'])
                && ('true' === $conf['firstPage'] || true === $conf['firstPage'])
            );
            $request->firstPage = (
                   array_key_exists('firstpage', $conf)
                && isset($conf['firstpage'])
                && ('true' === $conf['firstpage'] || true === $conf['firstpage'])
            );

            $request->merge =  ! (
                   array_key_exists('merge', $conf)
                && isset($conf['merge'])
                && ('false' === $conf['merge'] || false === $conf['merge'])
            );

            if (array_key_exists('filetype', $conf) && isset($conf['filetype'])) {
                if (is_array($conf['filetype'])) {
                    $request->fileTypes = [];
                    foreach ($conf['filetype'] as $type) {
                        $request->fileTypes []= mb_strtolower($type);
                    }
                } else {
                    $request->fileTypes = [mb_strtolower($conf['filetype'])];
                }
            }

            if (array_key_exists('fileType', $conf) && isset($conf['fileType'])) {
                if (is_array($conf['fileType'])) {
                    $request->fileTypes = [];
                    foreach ($conf['fileType'] as $type) {
                        $request->fileTypes []= mb_strtolower($type);
                    }
                } else {
                    $request->fileTypes = [mb_strtolower($conf['fileType'])];
                }
            }

            if (array_key_exists('x', $conf) && isset($conf['x'])) {
                $request->x = $conf['x'];
            }

            if (array_key_exists('y', $conf) && isset($conf['y'])) {
                $request->y = $conf['y'];
            }

            if (array_key_exists('color', $conf)
                && isset($conf['color'])
                && !('false' === $conf['color'] || false === $conf['color'])
            ) {
                $request->color = mb_strtolower($conf['color']);
            }

            $requests[$key] = $request;
        }
        return $requests;
    }
}