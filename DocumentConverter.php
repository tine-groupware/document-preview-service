<?php

class DocumentConverter
{
    protected $tempDir = '';
    protected $downDir = '';
    protected $downUrl = '';
    protected $logger;
    protected $config;

    public function __construct($tempDir, $downDir, $downUrl, $logger, $config)
    {
        $this->tempDir = $tempDir;
        $this->downDir = $downDir;
        $this->downUrl = $downUrl;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function __invoke($path, $uid, array $conf)
    {
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $imageExt = $this->config->get('imgExt', array('png'));
        if (false === is_array($imageExt)){
            $imageExt = $imageExt->toArray();
        }

        $docExt = $this->config->get('docExt', array('odt'));
        if (false === is_array($docExt)){
            $docExt = $docExt->toArray();
        }

        if(false === is_dir($this->tempDir.$uid)){
            if(false === mkdir($this->tempDir.$uid)) {
                return false;
            }
        } else {
            return false;
        }


        if(false === is_dir($this->downDir.$uid)){
            if(false === mkdir($this->downDir.$uid)) {
                return false;
            }
        } else {
            return false;
        }

        $rtn = array();
        try {
            $inputFileType = 'png';

            if (true === in_array($ext, $docExt)) {
                if (false === $this->convertToPDF($path, $uid)) {
                    return false;
                }
                if (isset($conf['onlyPdf'])) {
                    $rtn = array();
                    $err = 0;
                    exec('mv ' . escapeshellarg($this->tempDir . $uid . '/' . $name . '.pdf') . ' ' . $this->downDir . $uid . '/', $rtn, $err);
                    if (0 !== $err){
                        $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': failed to move pdf to download dir with ' . $err . ' ' . join(PHP_EOL, $rtn));
                        return false;
                    }
                } elseif (false === $this->covertToPNG($uid, $conf, $name)) {
                    return false;
                }
            } else if ('pdf' === $ext) {
                shell_exec('mv ' . $path . ' ' . $this->tempDir . $uid . '/');
                if (false === $this->covertToPNG($uid, $conf, $name)) {
                    return false;
                }
            } else if (true === in_array($ext, $imageExt)) {
                $cmd = 'mv ' . $path . ' ' . $this->tempDir . $uid . '/';
                $rtn = array();
                $err = 0;
                exec($cmd, $rtn, $err);
                if (0 !== $err) {
                    $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . join(PHP_EOL, $rtn));
                    return false;
                }
                $inputFileType = $ext;
            } else {
                return false;
            }

            // ... imagemagick
            if (!isset($conf['onlyPdf']) && false === $this->convertToSize($uid, $conf, $inputFileType, $name)) {
                return false;
            }

            if (isset($conf['onlyPdf'])) {
                $rtn = [[$this->downUrl . $uid . '/' . $name . '.pdf']];
            } else {
                $rtn = $this->getReturn($uid, $inputFileType, $conf);
            }

        } finally {
            $this->cleanUp($uid);
        }

        return $rtn;
    }

    // checks if all pages need to be converted
    protected function onlySingelPage($conf)
    {
        foreach ($conf as $cnf) {
            if (false === $cnf['firstPage']) {
                return false;
            }
        }
        return true;
    }

    // converts documents to pdf with soffice headless
    protected function convertToPDF($path, $uid)
    {
        $cmd = $this->config->get('ooBinary', 'soffice') . ' --convert-to pdf ' . $path . ' --outdir ' . $this->tempDir . $uid . ' --headless';
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . $cmd . ' failed with ' . $err . ' ' . join(PHP_EOL, $rtn));
            return false;
        }

        return true;
    }

    // converts pdf/ps to png for further prothessing
    protected function covertToPNG($uid, $conf, $name)
    {
        if (true === $this->onlySingelPage($conf)) {
            $cmd = 'gs -q -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT=2 "-sDEVICE=pngalpha" -dTextAlphaBits=4 -dGraphicsAlphaBits=4 "-r150x150" -sOutputFile=' . escapeshellarg($this->tempDir . $uid . '/' . $name . '001.png') . ' ' . escapeshellarg($this->tempDir . $uid . '/' . $name . '.pdf') . ' -c quit'; //to png $tempDir/$uid/$filename.png   from $tempDir/$uid/$filename.pdf
        } else {
            $cmd = 'gs -q -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT=2 "-sDEVICE=pngalpha" -dTextAlphaBits=4 -dGraphicsAlphaBits=4 "-r150x150" -sOutputFile=' . escapeshellarg($this->tempDir . $uid . '/' . $name . '%03d.png') . ' ' . escapeshellarg($this->tempDir . $uid . '/' . $name . '.pdf') . ' -c quit'; //to png $tempDir/$uid/filenameXXX.png   from $tempDir/$uid/$filename.pdf
        }
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . $cmd . ' failed with ' . $err . ' ' . join(PHP_EOL, $rtn));
            return false;
        }

        return true;
    }

    //converts to size and typ
    protected function convertToSize($uid, $conf, $inputFileType, $name)
    {
        foreach ($conf as $key => $cnf) {
            if (true === $cnf['firstPage']) {
                $nameAppend = '';
                $file = $this->tempDir;
                if (is_file($this->tempDir . $uid . '/' . $name . '001.' . $inputFileType)) {
                    $file .= escapeshellarg($uid . '/' . $name . '001.' . $inputFileType);
                } else {
                    $file .= escapeshellarg($uid . '/' . $name . '.' . $inputFileType);
                }
                $cmd = 'gm convert ' . $file . ' -resize ' . escapeshellarg($cnf['x'] . 'x' . $cnf['y']);
            } else {
                $nameAppend = '-%03d';
                $cmd = 'gm convert ' . $this->tempDir . $uid . '/*.' . $inputFileType . ' +adjoin -resize ' . escapeshellarg($cnf['x'] . 'x' . $cnf['y']);
            }
            if (!(false === $cnf['color'])) {
                $cmd .= ' -gravity center -background ' . escapeshellarg($cnf['color']) . ' -extent ' . escapeshellarg($cnf['x'] . 'x' . $cnf['y']);
            }
            $cmd .= ' ' . escapeshellarg($this->downDir . $uid . '/' . $key . $nameAppend . '.' . $cnf['filetype']);
            $rtn = array();
            $err = 0;
            exec($cmd, $rtn, $err);
            if (0 !== $err){
                $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . $cmd . ' failed with ' . $err . ' ' . join(PHP_EOL, $rtn));
                return false;
            }
        }
        return true;
    }

    protected function cleanUp($uid)
    {
        $cmd ='rm -r ' . $this->tempDir . $uid;
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': ' . join(PHP_EOL, $rtn));
        }
    }

    protected function getReturn($uid, $inputFileType, $conf)
    {
        $count = count(glob($this->tempDir . $uid . '/*.' . $inputFileType));
        $rtn = array();
        foreach ($conf as $key => $cnf) {
            if (false === $cnf['firstPage']) {
                $links = array();
                for ($i = 0; $i < $count; $i++) {
                    $file = $uid . '/' . $key . '-' . sprintf('%03d', $i) . '.' . $cnf['filetype'];
                    if (true === is_file($this->downDir.$file)){
                        $links[] = $this->downUrl . $file;
                    } else {
                        $this->logger->err(__METHOD__ . ' ' . __LINE__ . ': did not find file: ' . $this->downDir . $file);
                        return false;
                    }
                }
                $rtn[$key] = $links;
            } else {
                $rtn[$key] = array($this->downUrl . $uid . '/' . $key . '.' . $cnf['filetype']);
            }
        }
        return $rtn;
    }

    public static function checkConfig($conf){
        if( 0 === count($conf)){
            return false;
        }
        if (isset($conf['onlyPdf'])) {
            return true;
        }
        foreach ($conf as $key => $cnf){
            if( false === isset($cnf['firstPage']) ||
                false === isset($cnf['filetype']) ||
                false === isset($cnf['x']) ||
                false === isset($cnf['y']) ||
                false === isset($cnf['color'])
            ){
                return false;
            }
            if( false === is_bool($cnf['firstPage']) ||
                false === is_string($cnf['filetype']) ||
                false === is_int($cnf['x']) ||
                false === is_int($cnf['y']) ||
                false === (is_string($cnf['color']) || is_bool($cnf['color']))
            ){
                return false;
            }
        }
        return true;
    }
}

/*
JSON config {
    "Key(N)":[
    'firstPage': (true||false),
    'filetype' : '(image e.g. png||jpg)',
    'x' : (size in px),
    'y' : (size in px),
    'color' : '(color e.g. white||blue)' || 'false',
    ],
    ...
}

JSON return {
    "Key(N)":[
    '(link to image)',
    ...
    ],

    ||

    "Key(N)": '(link to image)',
    ...
}
*/

