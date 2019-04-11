<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$lock = new \DocumentService\Lock(false, 4, 4);

echo $lock->currentLocks();

$lock->clear();

