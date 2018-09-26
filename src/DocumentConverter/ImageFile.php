<?php namespace DocumentService\DocumentConverter;

use Exception;

class ImageFile extends File {
    function fitToSize($ext, $x, $y, $color = false): ImageFile {
        $path = Config::getInstance()->get('tempdir').uniqid('file_', true).'.'.$ext;

        $cmd = 'gm convert ' . $this->_path . ' -resize ' . escapeshellarg($x . 'x' . $y);
        if ($color == false) {
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