<?php declare(strict_types=1);

namespace DocumentService;

use Exception;

class Lock
{
    public const HIGH_PRIORITY = 1;
    public const LOW_PRIORITY = 0;

    private const USED = true;
    private const FREE = false;

    private const VARIABLE_ID = 5435;
    private const SHM_KEY = 3464;
    private const SLEEP_TIME = 10000;

    private $semIdentifier;
    private $shmIdentifier;
    private $slotSemIdentifier;
    private $slot;
    private $locked = false;
    private $priority;
    private $maxLowPrioritySlots;
    private $maxHighPrioritySlots;


    /**
     * Lock constructor.
     * @param $maxLowPrioritySlots
     * @param $maxHighPrioritySlots
     * @param $priority
     * @throws Exception invalid max Priority Slots
     * @throws Exception failed to generate ftok
     * @throws Exception failed to get semaphore
     * @throws Exception failed to attach shared memory
     */
    public function __construct(bool $priority, int $maxLowPrioritySlots, int $maxHighPrioritySlots)
    {
        if ($maxHighPrioritySlots > 126 || $maxLowPrioritySlots > 126) {
            throw new Exception("Max Priority Slot must not be lager than 126");
        }

        if ($maxHighPrioritySlots < 0 || $maxLowPrioritySlots < 0) {
            throw new Exception("Max Priority Slot must not be negative");
        }

        $ftok = ftok(__FILE__, chr(255));
        if (-1 === $ftok) {
            throw new Exception("Failed to generate ftok");
        }

        $this->semIdentifier = sem_get($ftok, 1);
        if (false === $this->semIdentifier) {
            throw new Exception("Failed to get semaphore");
        }

        $this->shmIdentifier = shm_attach(self::SHM_KEY);
        if (false === $this->semIdentifier) {
            throw new Exception("Failed to attach shared memory");
        }

        $this->maxHighPrioritySlots = $maxHighPrioritySlots;
        $this->maxLowPrioritySlots = $maxLowPrioritySlots;
        $this->priority = $priority === true ? self::HIGH_PRIORITY : self::LOW_PRIORITY;
    }

    public function __destruct()
    {
        $this->unlockLockedSlot();
    }

    /**
     * Trys to lock a free slot in priority, with a timeout
     *
     * @param int $timeout
     * @return bool
     * @throws Exception invalid priority
     * @throws Exception $slot value not in interval
     * @throws Exception Could not get ftok
     * @throws Exception Could not get semaphore
     * @throws Exception No slot is locked
     * @throws Exception Failed to release semaphore
     * @throws Exception Failed to acquire semaphore
     */
    public function lock(int $timeout): bool
    {
        if (true === $this->locked) {
            return true;
        }

        $endTime = time() + $timeout;
        do {
            //sleeps on unsuccessful acquire
            $semAcquired = $this->lockFreeSlot();
        } while (false === $semAcquired && time() > $endTime);

        return $semAcquired;
    }

    /**
     * List current locks
     * 0 => low priority
     * 1 => high priority
     *
     * @return bool[][]
     */
    public function currentLocks(): array
    {
        return $this->getUsageArray();
    }

    /**
     * Unlocks slot locked with lock
     *
     * @throws Exception No slot is locked
     * @throws Exception Failed to acquire semaphore
     * @throws Exception Failed to releases semaphore
     * @throws Exception Failed to write to shm
     */
    private function unlockLockedSlot()
    {
        if (false === $this->locked) {
            return;
        }

        try {
            //lock shared memory
            if (false === sem_acquire($this->semIdentifier)) {
                throw new Exception("Failed to acquire semaphore");
            }

            $this->releaseSlot();

            $this->updateUsageArray($this->slot, self::FREE);
        } finally {
            if (false === sem_release($this->semIdentifier)) {
                throw new Exception("Failed to releases semaphore");
            }
        }

        $this->slot = null;
        $this->slotSemIdentifier = null;
        $this->locked = false;
    }

    /**
     * Locks a free slot. If no slot is free sleeps for SLEEP_TIME
     *
     * @return bool locked Slot
     *
     * @throws Exception $slot value not in interval
     * @throws Exception Could not get ftok
     * @throws Exception Could not get semaphore
     * @throws Exception No slot is locked
     * @throws Exception Failed to release semaphore
     * @throws Exception Failed to acquire semaphore
     */
    private function lockFreeSlot(): bool
    {
        $usageArray = $this->getUsageArray();

        $slot = $this->findFreeSlot($usageArray);
        if (null === $slot) {
            //no free slot
            $slot = $this->checkSlotSemaphores($usageArray);
            if (null === $slot) {
                //all semaphores are locked corrected
                usleep(self::SLEEP_TIME);
                return false;
            }
        }

        return $this->lockSlot($slot);
    }

    /**
     * Checks if $usageArray is consistent with the slot semaphore locks
     * if not the first inconsistent slot is returned
     *
     * @param array $usageArray
     * @return int slot | null no slot freed
     *
     * @throws Exception $slot value not in interval
     * @throws Exception Could not get ftok
     * @throws Exception Could not get semaphore
     * @throws Exception No slot is locked
     * @throws Exception Failed to release semaphore
     */
    private function checkSlotSemaphores(array $usageArray): ?int
    {
        foreach ($usageArray[$this->priority] as $slot => $usage) {
            //check semaphore
            if (self::USED === $usage && true === $this->acquireSlot($slot)) {
                $this->releaseSlot();
                return $slot;
            }
        }

        return null;
    }

    /**
     * locks slot, if slot is not already in use
     *
     * @param int $slot
     * @return bool
     * @throws Exception Failed to acquire semaphore
     * @throws Exception Failed to release semaphore
     * @throws Exception $slot value not in interval
     * @throws Exception Could not get ftok
     * @throws Exception Could not get semaphore
     */
    private function lockSlot(int $slot): bool
    {
        try {
            //lock shared memory
            if (false === sem_acquire($this->semIdentifier)) {
                throw new Exception("Failed to acquire semaphore");
            }

            //check lock, and lock
            if (false === $this->acquireSlot($slot)) {
                return false;
            }

            $this->slot = $slot;

            $this->updateUsageArray($slot, self::USED);
        } finally {
            if (false === sem_release($this->semIdentifier)) {
                throw new Exception("Failed to release semaphore");
            }
        }

        $this->locked = true;

        return true;
    }

    /**
     * Finds free slot for current priority in $usageArray
     *
     * @param array $usageArray
     * @return int free slot | null no free slot found
     */
    private function findFreeSlot(array $usageArray): ?int
    {
        //check if free slot is available
        if (self::LOW_PRIORITY === $this->priority) {
            $usage_count = 0;
            foreach ($usageArray as $array) {
                foreach ($array as $usage) {
                    if (self::USED === $usage) {
                        $usage_count++;
                    }
                }
            }

            if ($usage_count > $this->maxLowPrioritySlots) {
                return null;
            }
        }

        // find free slot
        foreach ($usageArray[$this->priority] as $slot => $usage) {
            if (self::FREE === $usage) {
                return $slot;
            }
        }

        // no free High Priority Slot found
        return null;
    }

    /**
     * @param int $slot interval [0..127)
     * @return bool successfully acquired semaphore
     * @throws Exception $slot value not in interval
     * @throws Exception Could not get ftok
     * @throws Exception Could not get semaphore
     */
    private function acquireSlot(int $slot): bool
    {
        // keep one slot for shared memory semaphore
        if ($slot > 126) {
            throw new Exception("slot must not be lager than 126");
        }

        if ($slot < 0) {
            throw new Exception("slot must not be negative");
        }

        // set high prio bit
        if (self::HIGH_PRIORITY == $this->priority) {
            $slot += 128;
        }

        $key = ftok(__FILE__, chr($slot));
        if (-1 == $key) {
            throw new Exception('Could not get ftok');
        }

        $this->slotSemIdentifier = sem_get($key, 1);
        if (false == $this->slotSemIdentifier) {
            throw new Exception('Could not get semaphore');
        }

        return sem_acquire($this->slotSemIdentifier, true);
    }

    /**
     * Releases slot semaphore which was locked with acquireSlot
     *
     * @throws Exception No slot is locked
     * @throws Exception Failed to release semaphore
     */
    private function releaseSlot()
    {
        if (null === $this->slotSemIdentifier) {
            throw new Exception("Can not release semaphore. Semaphore not set");
        }

        if (false === sem_release($this->slotSemIdentifier)) {
            throw new Exception("Failed to release semaphore");
        }
    }

    /**
     * Reads slot usage from shared memory, if shm is not initialized
     * an initial usage array is returned.
     * No shm lock is needed
     *
     * @return bool[][] slot usage
     */
    private function getUsageArray(): array
    {
        // suppress warning "variable key 0 doesn't exist"
        //   when shared memory is not already initialized
        $usageArray = @ shm_get_var($this->shmIdentifier, self::VARIABLE_ID);
        if (false === $usageArray) {
            // shared memory not initialize, all slots are free
            // do not save here this function is called without locking
            return [
                self::LOW_PRIORITY => array_fill(0, $this->maxLowPrioritySlots, self::FREE),
                self::HIGH_PRIORITY => array_fill(0, $this->maxHighPrioritySlots, self::FREE),
            ];
        }

        return $usageArray;
    }

    /**
     * Updates usage array in shared memory.
     * This function should only be called if the shm is locked
     *
     * @param int $slot
     * @param bool $state
     * @throws Exception Failed to write to shm
     */
    private function updateUsageArray(int $slot, bool $state)
    {
        $usageArray = $this->getUsageArray();
        $usageArray[$this->priority][$slot] = $state;

        if (false === shm_put_var($this->shmIdentifier, self::VARIABLE_ID, $usageArray)) {
            throw new Exception("Failed to write to shared memory");
        }
    }
}