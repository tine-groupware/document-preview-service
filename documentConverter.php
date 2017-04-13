<?php

/*
 * TODO Error Handeling
 */

class documentConverter
{
    public function __invoke($path, $uid, $conf, $tempDir, $downDir, $downUrl)
    {
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext = pathinfo($path, PATHINFO_EXTENSION);


        $docExt = array('odt', 'ods', 'doc');
        $imageExt = array('jpg', 'png', 'gif');

        if (!is_dir($downDir)) {
            mkdir($downDir);
        }
        mkdir($downDir . $uid);

        $inputFileType = 'png';

        if (in_array($ext, $docExt)) {
            $this->convertToPDF($path, $uid, $tempDir);
            $this->covertToPNG($uid, $tempDir, $conf, $name);
        } else if ($ext == 'pdf') {
            shell_exec('mv ' . $path . ' ' . $tempDir . $uid . '/');
            $this->covertToPNG($uid, $tempDir, $conf, $name);
        } else if (in_array($ext, $imageExt)) {
            shell_exec('mv ' . $path . ' ' . $tempDir . $uid . '/');
            $inputFileType = $ext;
        } else {
            return false;
        }

        // ... imagemagick
        $this->convertToSize($uid, $tempDir, $downDir, $conf, $inputFileType, $name);

        $rtn = $this->getJson($tempDir, $uid, $inputFileType, $conf, $downUrl);

        $this->cleanUp($uid, $tempDir);

        return json_encode($rtn);
    }

    // checks if all pages need to be converted
    protected function onlySingelPage($conf)
    {
        foreach ($conf as $cnf) {
            if ($cnf['thumbtnail'] == false) {
                return false;
            }
        }
        return true;
    }

    // converts documents to pdf with soffice headless
    protected function convertToPDF($path, $uid, $tempDir)
    {
        shell_exec('soffice --convert-to pdf ' . $path . ' --outdir ' . $tempDir . $uid . ' --headless'); // to pdf $tempDir/$uid/$filename.pdf from $path
    }

    // converts pdf/ps to png for further prothessing
    protected function covertToPNG($uid, $tempDir, $conf, $name)
    {
        if ($this->onlySingelPage($conf)) {
            shell_exec('gs -dNOPAUSE -sDEVICE=png16m -sOutputFile=' . $tempDir . $uid . '/' . $name . '.png ' . $tempDir . $uid . '/' . $name . '.pdf -c quit'); //to png $tempDir/$uid/$filename.png   from $tempDir/$uid/$filename.pdf
        } else {
            shell_exec('gs -dNOPAUSE -sDEVICE=png16m -sOutputFile=' . $tempDir . $uid . '/' . $name . '%03d.png ' . $tempDir . $uid . '/' . $name . '.pdf -c quit'); //to png $tempDir/$uid/filenameXXX.png   from $tempDir/$uid/$filename.pdf
        }
    }

    //converts to size and typ
    protected function convertToSize($uid, $tempDir, $downDir, $conf, $inputFileType, $name)
    {
        foreach ($conf as $key => $cnf) {
            if ($cnf['thumbtnail']) {
                $cmd = 'convert ' . $tempDir . $uid . '/' . $name . '001.' . $inputFileType . ' -resize ' . $cnf['x'] . 'x' . $cnf['y'];
            } else {
                $cmd = 'convert ' . $tempDir . $uid . '/*.' . $inputFileType . ' -resize ' . $cnf['x'] . 'x' . $cnf['y'];
            }
            if (!($cnf['color'] == false)) {
                $cmd = $cmd . ' -gravity center -background ' . $cnf['color'] . ' -extent ' . $cnf['x'] . 'x' . $cnf['y'];
            }
            $cmd = $cmd . ' ' . $downDir . $uid . '/' . $key . '.' . $cnf['filetype'];
            shell_exec($cmd);
        }
    }

    protected function cleanUp($uid, $tempDir)
    {
        shell_exec('rm -r ' . $tempDir . $uid);
    }

    protected function getJson($tempDir, $uid, $inputFileType, $conf, $downUrl)
    {
        $count = count(glob($tempDir . $uid . '/*.' . $inputFileType));
        $rtn = array();
        foreach ($conf as $key => $cnf) {
            if (!$cnf['thumbtnail']) {
                $links = array();
                for ($i = 0; $i < $count; $i++) {
                    array_push($links, $downUrl . $uid . '/' . $key . '-' . $i . '.' . $cnf['filetype']);
                }
                $rtn[$key] = $links;
            } else {
                $rtn[$key] = $downUrl . $uid . '/' . $key . '.' . $cnf['filetype'];
                // ? solle es ein String in einem Array oder nur eine String
            }
        }
        return $rtn;
    }
}

$path = 'imATestFile.odt';
$uid = '435e3d3ea34';
$downDir = 'test/downDir/';
$tempDir = 'test/tempDir/';
$downUrl = "nsa.gov/files/";
$conf = array(
    'test1' => array(
        'x' => 400,
        'y' => 800,
        'color' => 'blue',
        'filetype' => 'jpg',
        'thumbtnail' => false,
    ),
    'test2' => array(
        'x' => 400,
        'y' => 800,
        'color' => false,
        'filetype' => 'png',
        'thumbtnail' => false,
    ),
    'thumbt' => array(
        'x' => 300,
        'y' => 300,
        'color' => 'none',
        'filetype' => 'jpeg',
        'thumbtnail' => true,
    ),
);

$foo = new documentConverter();
echo $foo($path, $uid, $conf, $tempDir, $downDir, $downUrl);

/*
JSON config {
    "Key(N)":[
    'thumbtnail': (true||false),
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