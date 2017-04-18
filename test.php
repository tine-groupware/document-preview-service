<?php
$ipcId = ftok(__FILE__, 'a');
$semaphore = sem_get($ipcId, 1);


try {

    echo 'sem_acquireing';
    $timeStarted = time();
    do {
        $test = sem_acquire($semaphore, true);
        usleep(10000);
    } while (false === $test && time() - $timeStarted < 5);
    if (false === $test) {
        echo 'failed to get semaphore';
        return;
    }
    echo 'sleep 20s ';
    sleep(20);
    echo 'success ';

} finally {
    if (null !== $semaphore && false !== $test) {
        echo 'finally';
        sem_release($semaphore);
    }
}
