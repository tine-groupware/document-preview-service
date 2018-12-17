<?php
namespace DocumentService;

use Zend\Log\Logger;

class Lock
{
    public const HIGHPRIORITY = true;
    public const LOWPRIORITY = false;


    private $semid;
    private $prio;
    private $key;
    private $locked = false;
    private $maxLowPrio;
    private $maxHighPrio;

    /**
     * Lock constructor.
     * @param bool $prio
     * @param int $maxLowPrio
     * @param int $maxHighPrio
     * @throws DocumentPreviewException
     */
    public function __construct(bool $prio, int $maxLowPrio, int $maxHighPrio)
    {
        $this->prio = $prio;
        $this->maxLowPrio = $maxLowPrio;
        $this->maxHighPrio = $maxHighPrio;
        $this->key = ftok(__FILE__, 'f');
        if (-1 == $this->key) {
            throw new DocumentPreviewException('Could not get ftok', 0);
        }
        $this->semid = sem_get($this->key, 1);
        if (false == $this->semid) {
            throw new DocumentPreviewException('Could not get semaphore', 0);
        }
    }

    /**
     * List current locks
     * 0 => low prio
     * 1 => high prio
     *
     * @return array
     * @throws DocumentPreviewException
     */
    public function currentLocks(): array
    {
        if (false == sem_acquire($this->semid)) {
            throw new DocumentPreviewException('Failed to acquire semaphore', 0);
        }

        $shm_id = shmop_open($this->key, 'n', 0644, 2);
        if (false != $shm_id) {
            $locks = [1 => $this->maxLowPrio, 2=> $this->maxHighPrio];
        } else {
            $shm_id = shmop_open($this->key, 'c', 0644, 2);
            if (false == $shm_id) {
                throw new DocumentPreviewException('Failed to open shared memory', 0);
            }
            $mem = shmop_read($shm_id, 0, 2);
            if (false == $mem) {
                throw new DocumentPreviewException('Failed to read shared memory', 0);
            }
            $locks = unpack('C*', $mem);
        }

        shmop_close($shm_id);
        if (false == sem_release($this->semid)) {
            (ErrorHandler::getInstance())->log(Logger::ERR, "Failed to release semaphore", __METHOD__);
        }

        return [0 => $locks[1], 1 => $locks[2]];
    }

    /**
     * Locks if:
     *   LOWPRIORITY: low prio current locks + high prio current locks < maxLowPrio
     *   HIGHPRIORITY: high prio current locks < maxHighPrio
     *
     * @return bool true if lock successful or already locked, otherwise false
     * @throws DocumentPreviewException
     */
    public function lock(): bool
    {
        if (true == $this->locked) {
            return true;
        }

        if (false == sem_acquire($this->semid)) {
            throw new DocumentPreviewException('Failed to acquire semaphore', 0);
        }

        $shm_id = shmop_open($this->key, 'n', 0644, 2);
        if (false != $shm_id) {
            $locks = [1 => $this->maxLowPrio, 2=> $this->maxHighPrio];
        } else {
            $shm_id = shmop_open($this->key, 'c', 0644, 2);
            if (false == $shm_id) {
                throw new DocumentPreviewException('Failed to open shared memory', 0);
            }
            $mem = shmop_read($shm_id, 0, 2);
            if (false == $mem) {
                throw new DocumentPreviewException('Failed to read shared memory', 0);
            }
            $locks = unpack('C*', $mem);
        }

        if (Lock::LOWPRIORITY == $this->prio && $locks[1] - ($this->maxHighPrio - $locks[2]) > 0) {
            $locks[1]--;
        } elseif (Lock::HIGHPRIORITY == $this->prio && $locks[2] > 0) {
            $locks[2]--;
        } else {
            shmop_close($shm_id);
            if (false == sem_release($this->semid)) {
                (ErrorHandler::getInstance())->log(Logger::ERR, "Failed to release semaphore", __METHOD__);
            }
            return false;
        }

        if (false == shmop_write($shm_id, pack('C*', $locks[1], $locks[2]), 0)) {
            throw new DocumentPreviewException('Failed to write to shared memory', 0);
        }

        $this->locked = true;

        shmop_close($shm_id);
        if (false == sem_release($this->semid)) {
            (ErrorHandler::getInstance())->log(Logger::ERR, "Failed to release semaphore", __METHOD__);
        }

        return true;
    }


    /**
     * Unlocks
     *
     * @return bool true if unlock successful or already unlocked, otherwise false
     * @throws DocumentPreviewException
     */
    public function unlock(): bool
    {
        if (false == $this->locked) {
            return true;
        }

        if (false == sem_acquire($this->semid)) {
            throw new DocumentPreviewException('Failed to acquire semaphore', 0);
        }

        $shm_id = shmop_open($this->key, 'c', 0644, 2);
        if (false == $shm_id) {
            throw new DocumentPreviewException('Failed to open shared memory', 0);
        }

        $mem = shmop_read($shm_id, 0, 2);
        if (false == $mem) {
            throw new DocumentPreviewException('Failed to read shared memory', 0);
        }
        $locks = unpack('C*', $mem);

        if (Lock::LOWPRIORITY == $this->prio) {
            $locks[1]++;
        } elseif (Lock::HIGHPRIORITY == $this->prio) {
            $locks[2]++;
        }

        if (false == shmop_write($shm_id, pack('C*', $locks[1], $locks[2]), 0)) {
            throw new DocumentPreviewException('Failed to write to shared memory', 0);
        }

        shmop_close($shm_id);
        if (false == sem_release($this->semid)) {
            (ErrorHandler::getInstance())->log(Logger::ERR, "Failed to release semaphore", __METHOD__);
        }

        return true;
    }
}

