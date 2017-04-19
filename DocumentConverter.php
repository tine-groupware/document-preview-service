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

    public function __invoke($path, $uid, $conf)
    {
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        $imageExt = $this->config->get('imgExt', array());
        if (false === is_array($imageExt)){
            $imageExt = $imageExt->toArray();
        }

        $docExt = $this->config->get('docExt', array());
        if (false === is_array($docExt)){
            $docExt = $docExt->toArray();
        }

        if(false === mkdir($this->downDir . $uid)) {
            return false;
        }

        $inputFileType = 'png';

        if (true === in_array($ext, $docExt)) {
            $this->convertToPDF($path, $uid);
            $this->covertToPNG($uid, $conf, $name);
        } else if ('pdf' === $ext) {
            shell_exec('mv ' . $path . ' ' . $this->tempDir . $uid . '/');
            $this->covertToPNG($uid, $conf, $name);
        } else if ( true === in_array($ext, $imageExt)) {
            $cmd = 'mv ' . $path . ' ' . $this->tempDir . $uid . '/';
            $rtn = array();
            $err = 0;
            exex($cmd, $rtn, $err);
            if (0 !== $err){
                $this->logger->err(join(PHP_EOL, $rtn));
                return -1;
            }
            $inputFileType = $ext;
        } else {
            return false;
        }

        // ... imagemagick
        $this->convertToSize($uid, $conf, $inputFileType, $name);

        $rtn = $this->getReturn($uid, $inputFileType, $conf);

        $this->cleanUp($uid);

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
        $cmd = 'soffice --convert-to pdf ' . $path . ' --outdir ' . $this->tempDir . $uid . ' --headless';
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            $this->logger->err(join(PHP_EOL, $rtn));
            return -1;
        }
    }

    // converts pdf/ps to png for further prothessing
    protected function covertToPNG($uid, $conf, $name)
    {
        echo $this->onlySingelPage($conf);
        if (true === $this->onlySingelPage($conf)) {
            $cmd = 'gs -dNOPAUSE -sDEVICE=png16m -sOutputFile=' . $this->tempDir . $uid . '/' . $name . '.png ' . $this->tempDir . $uid . '/' . $name . '.pdf -c quit'; //to png $tempDir/$uid/$filename.png   from $tempDir/$uid/$filename.pdf
        } else {
            $cmd = 'gs -dNOPAUSE -sDEVICE=png16m -sOutputFile=' . $this->tempDir . $uid . '/' . $name . '%03d.png ' . $this->tempDir . $uid . '/' . $name . '.pdf -c quit'; //to png $tempDir/$uid/filenameXXX.png   from $tempDir/$uid/$filename.pdf
        }
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            $this->logger->err(join(PHP_EOL, $rtn));
            return -1;
        }
    }

    //converts to size and typ
    protected function convertToSize($uid, $conf, $inputFileType, $name)
    {
        foreach ($conf as $key => $cnf) {
            if (true === $cnf['firstPage']) {
                $cmd = 'convert ' . $this->tempDir . $uid . '/' . $name . '001.' . $inputFileType . ' -resize ' . escapeshellarg($cnf['x']) . 'x' . escapeshellarg($cnf['y']);
            } else {
                $cmd = 'convert ' . $this->tempDir . $uid . '/*.' . $inputFileType . ' -resize ' . escapeshellarg($cnf['x']) . 'x' . escapeshellarg($cnf['y']);
            }
            if (!(false === $cnf['color'])) {
                $cmd = $cmd . ' -gravity center -background ' . escapeshellarg($cnf['color']) . ' -extent ' . escapeshellarg($cnf['x']) . 'x' . escapeshellarg($cnf['y']);
            }
            $cmd = $cmd . ' ' . $this->downDir . $uid . '/' . escapeshellarg($key) . '.' . escapeshellarg($cnf['filetype']);
            $rtn = array();
            $err = 0;
            exec($cmd, $rtn, $err);
            if (0 !== $err){
                $this->logger->err(join(PHP_EOL, $rtn));
                return -1;
            }
        }
    }

    protected function cleanUp($uid)
    {
        $cmd ='rm -r ' . $this->tempDir . $uid;
        $rtn = array();
        $err = 0;
        exec($cmd, $rtn, $err);
        if (0 !== $err){
            $this->logger->err(join(PHP_EOL, $rtn));
            return -1;
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
                    $file = $uid . '/' . $key . '-' . $i . '.' . $cnf['filetype'];
                    if (true === is_file($this->downDir.$file)){
                        array_push($links, $this->downUrl . $file);
                    } else {
                        return false;
                    }
                }
                $rtn[$key] = $links;
            } else {
                $rtn[$key] = $this->downUrl . $uid . '/' . $key . '.' . $cnf['filetype'];
                // ? solle es ein String in einem Array oder nur eine String
            }
        }
        return $rtn;
    }

    public static function checkConfig($conf){
        if( 0 === count($conf)){
            return false;
        }
        foreach ($conf as $key => $cnf){
            if( false === isset($key) &&
                false === isset($cnf['firstPage']) &&
                false === isset($cnf['filetype']) &&
                false === isset($cnf['x']) &&
                false === isset($cnf['y']) &&
                false === isset($cnf['color'])
            ){
                return false;
            }
            if( false === is_bool($cnf['firstPage']) &&
                false === is_string($cnf['filetype']) &&
                false === is_int($cnf['x']) &&
                false === is_int($cnf['y']) &&
                false === is_string($cnf['color'])
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
