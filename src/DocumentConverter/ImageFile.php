<?php declare(strict_types=1);

namespace DocumentService\DocumentConverter;

use DocumentService\DocumentPreviewException;
use Exception;

/**
 * Repesents a Image file
 * Deletes file on destruction
 * @package DocumentService\DocumentConverter
 */
class ImageFile extends File
{

    /**
     * Converts image types and fit image to specs using graphicsmagick
     * resize image to fit in x y with out stretching
     * if color is != false the empty area gets filled with color
     *
     * @param string $ext   finale extension
     * @param int    $x     finale image max width
     * @param int    $y     finale image max height
     * @param bool   $color fill color
     *
     * @return ImageFile
     * @throws DocumentPreviewException graphicsmagick operation failed
     * @throws DocumentPreviewException config not initialized
     */
    function fitToSize($ext, $x, $y, $color = false): ImageFile
    {
        $path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.$ext;

        $cmd = 'gm convert ' . $this->_path . ' -resize ' . escapeshellarg($x . 'x' . $y);
        if (false != $color) {
            $cmd .= ' -gravity center -background ' . escapeshellarg($color) . ' -extent ' . escapeshellarg($x . 'x' . $y);
        }
        $cmd .= ' ' . escapeshellarg($path);
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err) {
            throw new DocumentPreviewException('graphicsmagick operation failed', 801, 500);
        }

        return new ImageFile($path, true);
    }
}