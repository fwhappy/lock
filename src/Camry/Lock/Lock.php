<?php
namespace Camry\Lock;

class Lock
{
    /**
     * @param \Redis $Redis
     * @param int $iLockRetryLoopMS
     */
    public function __construct($Redis, $iLockRetryLoopMS = 10)
    {
        $this->instance         = $Redis;
        $this->iLockRetryLoopUS = $iLockRetryLoopMS * 1000;
    }

    /**
     * @param string $sLockKey
     * @param int $iTimeout (单位S); 获取锁失败后: 0:立刻返回false / >0 等待时间 / other:永久阻塞(null);
     *
     * @return bool
     */
    public function check($sLockKey, $iTimeout = 0)
    {
        $Inst = $this->_getInst();
        if ($iTimeout === 0) {
            $bRS = $Inst->get($sLockKey);
        } elseif ($iTimeout > 0) {
            $iT1 = microtime(true);
            do {
                $bRS = $Inst->get($sLockKey);
                if (!$bRS || ((microtime(true) - $iT1) > $iTimeout)) {
                    break;
                }
                usleep($this->iLockRetryLoopUS);
            } while (true);
        } else {
            do {
                $bRS = $Inst->get($sLockKey);
                if (!$bRS) {
                    break;
                }
                usleep($this->iLockRetryLoopUS);
            } while (true);
        }

        return !$bRS;
    }

    /**
     * @param string $sLockKey
     * @param int $iExpire  (单位S); >0:锁过期时间 / other:永不过期(null)
     * @param int $iTimeout (单位S); 获取锁失败后: 0:立刻返回false / >0 等待时间 / other:永久阻塞(null);
     *
     * @return bool
     */
    public function acquire($sLockKey, $iTimeout = 60, $iExpire = 60)
    {
        $Inst = $this->_getInst();

        if ($iTimeout === 0) {
            $bRS = $Inst->setnx($sLockKey, 1);
        } elseif ($iTimeout > 0) {
            $iT1 = microtime(true);
            do {
                $bRS = $Inst->setnx($sLockKey, 1);
                if ($bRS || ((microtime(true) - $iT1) > $iTimeout)) {
                    break;
                }
                usleep($this->iLockRetryLoopUS);
            } while (true);
        } else {
            do {
                $bRS = $Inst->setnx($sLockKey, 1);
                if ($bRS) {
                    break;
                }
                usleep($this->iLockRetryLoopUS);
            } while (true);
        }

        if ($iExpire > 0 && $bRS) {
            $try = 3;
            do {
                $resExpire = $Inst->expire($sLockKey, $iExpire);
                if ($resExpire) {
                    break;
                }
            } while (--$try);
            if (!$resExpire) {
                // exit("[redisdeadlock]:key:{$sLockKey}");
            }
        }

        return $bRS;
    }

    /**
     * @return bool
     */
    public function release($sLockKey)
    {
        $resDel = $this->_getInst()->del($sLockKey);
        if (!$resDel) {
            // exit("[redisdeadlock]:key:{$sLockKey}");
        }

        return $resDel;
    }


    /**
     * @return \Redis
     */
    public function _getInst()
    {
        return $this->instance;
    }
}