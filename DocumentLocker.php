<?php

class DocumentLocker
{
    protected $dir;
    protected $concurrentCount;
    protected $timeout;
    protected $prefix;
    protected $fh;
    protected $locked = false;

    public function __construct($dir, $concurrentCount, $timeout, $prefix)
    {
        $this->dir = rtrim($dir, '/') . '/';
        if (!is_writeable($this->dir)) {
            throw new DocumentLockerException('lock directory not writeable');
        }

        $this->concurrentCount = $concurrentCount;
        $this->timeout = $timeout;
        $this->prefix = $prefix;
    }

    public function acquire()
    {
        if ($this->locked) {
            throw new DocumentLockerException('this lock is already locked, you can\'t acquire twice');
        }
        $timeStarted = time();
        do {
            for ($i = 0; $i < $this->concurrentCount; ++$i) {
                if (false === ($this->fh = fopen($this->dir . $this->prefix . 'lockfile.' . $i, 'c'))) {
                    throw new DocumentLockerException('could not open lock file');
                }
                if ($this->locked = flock($this->fh, LOCK_EX | LOCK_NB)) {
                    break 2;
                }
                fclose($this->fh);
            }
            // sleep 50 ms
            usleep(50000);
        } while (time() - $timeStarted < $this->timeout);

        return $this->locked;
    }

    public function release()
    {
        if ($this->locked) {
            flock($this->fh, LOCK_UN);
            fclose($this->fh);
            $this->locked = false;
        }
    }

    public function __destruct()
    {
        $this->release();
    }
}