<?php namespace DocumentService\DocumentConverter;

use Exception;

/**
 * Repesents a Image file
 * Deletes file on destruction
 * @package DocumentService\DocumentConverter
 */
class ImageFile extends File {

    /**
     * Converts image types and fit image to specs using graphicsmagick
     * resize image to fit in x y with out stretching
     * if color is != false the empty area gets filled with color
     *
     * @param $ext finale extension
     * @param $x finale image max width
     * @param $y finale image max hight
     * @param bool $color fill color
     * @return ImageFile
     * @throws Exception
     */
    function fitToSize($ext, $x, $y, $color = false): ImageFile {
        $path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.$ext;

        $cmd = 'gm convert ' . $this->_path . ' -resize ' . escapeshellarg($x . 'x' . $y);
        if (false == $color) {
            $cmd .= ' -gravity center -background ' . escapeshellarg($color) . ' -extent ' . escapeshellarg($x . 'x' . $y);
        }
        $cmd .= ' ' . escapeshellarg($path);
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            throw new Exception('graphicsmagick operation failed', 50151);
        }

        return new ImageFile($path, true);
    }
}