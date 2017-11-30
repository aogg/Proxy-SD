<?php

namespace app\Helper;

/**
 * yield版本的互斥锁
 * 存在一个锁超时，多个阻塞程序同时进入主程序
 * Class RedisMutex
 *
 * @see composer require malkusch/lock
 * @package app\Helper
 */
class RedisMutex
{
    
    
    
    /**
     * @var null|\Redis
     */
    protected $redis = null;
    
    
    protected $script = '
            if redis.call("get",KEYS[1]) == ARGV[1] then
                return redis.call("del",KEYS[1])
            else
                return 0
            end
        ';
    
    
    protected $timeout = 3;
    
    protected $looping;
    
    protected $acquired;
    
    protected $token;
    
    
    protected $cachePrefix = 'redisMutexLock:';
    
    
    /**
     * RedisMutex constructor.
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
        $this->seedRandom();
    }
    
    
    public function tryLock($key, $callback)
    {
        // 处理异常的lock
        try{
            yield $this->lock($key);
            yield call_user_func($callback);
        }catch (\Exception $exception){
            yield $this->unlock($key);
            throw $exception;
        }
        
        return true;
    }
    
    
    public function lock($key)
    {
        $bool = yield $this->execute(function () use($key) { // 当前回调会在循环中执行
            $this->acquired = microtime(true);
            
            /*
             * The expiration time for the lock is increased by one second
             * to ensure that we delete only our keys. This will prevent the
             * case that this key expires before the timeout, and another process
             * acquires successfully the same key which would then be deleted
             * by this process.
             */
            if (yield $this->set($key, $this->timeout + 1)) { // 成功获取到锁
                $this->end();
            }
            
            return true;
        });
        
        if (!$bool){
            yield $this->unlock($key);
        }
        
        return $bool;
    }
    
    
    public function unlock($key)
    {
        $elapsed = microtime(true) - $this->acquired;
        if ($elapsed >= $this->timeout) { // 解锁超时，已经超过一倍的超时时间就不删除锁
            return true; // 原有的是抛出异常
        }
        
        /*
         * Worst case would still be one second before the key expires.
         * This guarantees that we don't delete a wrong key.
         */
        return yield !$this->release($key);
    }
    
    
    /**
     * Repeats executing a code until it was succesful.
     *
     * The code has to be designed in a way that it can be repeated without any
     * side effects. When execution was successful it should notify that event
     * by calling {@link Loop::end()}. I.e. the only side effects
     * of the code may happen after a successful execution.
     *
     * If the code throws an exception it will stop repeating the execution.
     *
     * @param callable $code The executed code block.
     * @return mixed The return value of the executed block.
     *
     */
    public function execute(callable $code)
    {
        $this->looping = true;
        $minWait = 100;
        $timeout = microtime(true) + $this->timeout;
        $result = false;
        for ($i = 0; $this->looping && microtime(true) < $timeout; $i++) {
    
            $result = yield call_user_func($code); // 这里处理looping
            
            if (!$this->looping) {
                break;
            }
            $min    = $minWait * pow(2, $i);
            $max    = $min * 2;
            
            yield usleep(rand($min, $max));
        }
        
        if (microtime(true) >= $timeout) {
            return false;
        }
        
        return $result;
    }
    
    
    /**
     * 结束掉execute的循环
     */
    public function end()
    {
        $this->looping = false;
    }
    
    
    
    
    protected function handleKey($key)
    {
        return $this->cachePrefix . $key;
    }
    
    
    public function set($key, $timeout = null)
    {
        $timeout = $timeout ?: $this->timeout + 1;
        $key = $this->handleKey($key);
        
        // swoole不支持set给参数
//        return yield $this->redis->set($this->handleKey($key), $this->getToken() . "NX EX {$timeout}");
        
        $bool = yield $this->redis->setnx($key, $this->getToken());
        if (yield !$this->redis->expire($key, $timeout)) {
            yield $this->redis->expire($key, $timeout);
        }
        
        return $bool;
    }

    
    
    protected function getToken()
    {
        if (is_null($this->token)) {
            $this->token = mt_rand();
        }
        
        return $this->token;
    }
    
    
    protected function evalScript($script, $arguments, $numKeys)
    {
        return yield $this->redis->eval($script, $arguments, $numKeys);
    }
    
    
    /**
     * Seeds the random number generator.
     *
     * Normally you don't need to seed, as this happens automatically. But
     * if you experience a {@link LockReleaseException} this might come
     * from identically created random tokens. In this case you could seed
     * from /dev/urandom.
     *
     * @param int|null $seed The optional seed.
     */
    public function seedRandom($seed = null)
    {
        is_null($seed) ? srand() : srand($seed);
    }
    
    
    
    
    



    
    
    protected function release($key)
    {
        return yield $this->evalScript($this->script, [$this->handleKey($key), $this->getToken()], 1);
    }
    
}