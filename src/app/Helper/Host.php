<?php

namespace app\Helper;


class Host
{
    
    
    protected $redisMutex;
    
    
    public function getHost($host)
    {
        try{
            return yield $this->getHostUseTry($host);
        }catch (\Exception $exception){
            return [
                'ip' => $host,
                'ipBool' => false,
            ];
        }
    }
    
    
    protected function getHostUseTry($host)
    {
        $data = yield $this->getRedis()->getCoroutine()->hGet($this->getRedisHashName(), $host);
        $time = 0;
        $ipBool = false;
        
        if (is_null($data)){ // 第一次也要等swoole_async_dns_lookup返回
            if (is_null($data) && yield $this->getRedisMutex()->set($host)){// 给swoole_async_dns_lookup加锁
                $this->asyncDnsLookUp($host, $time);
            }
            
            // 不更新dns的程序跑来这里等待
            $data = yield $this->getRedisMutex()->execute(function ()use($host){
                $data = yield $this->getRedis()->getCoroutine()->hGet($this->getRedisHashName(), $host);
    
                if (!is_null($data)) {
                    $this->getRedisMutex()->end();
                }
                
                return $data;
            });
        }
        
    
        // 正式处理缓存数据
        if (is_null($data)) {
            $ip = $host;
        } else {
            try{
                $data = \Swoole\Serialize::unpack($data);
            }catch (\Exception $exception){ // todo 无法捕获
                $data = [];
            }
            
            if (isset($data['ip']) && $data['ip'] === false){ // ip设为false时，返回false
                $ip = false;
                $time = $data['time'];
            }else if(isset($data['ip'])){ // 正常处理
                $ip = $this->getIpMap($data['ip']);
                if ($ip !== false){
                    $ipBool = true;
                    $time = $data['time'];
                }else{
                    $ip = $host;
                }
            }else{
                $ip = $host;
            }
        }
    
        $this->asyncDnsLookUp($host, $time);
        
        return [
            'ip' => $ip,
            'ipBool' => $ipBool,
        ];
    }
    
    
    protected function getIpMap($ip)
    {
        $data = get_instance()->config->get('proxy.ipMap');
    
        return isset($data[$ip]) ? $data[$ip] : $ip;
    }
    
    
    protected function getRedisHashName()
    {
        return __METHOD__;
    }
    
    
    public function getHostLockName($host)
    {
        return $this->getRedisHashName() . ':' . $host . '_lock';
    }
    
    
    
    protected function getRedis()
    {
        return get_instance()->redis_pool;
    }
    
    
    protected function asyncDnsLookUp($host, $time)
    {
        if (empty($time) || $time < time()){
            swoole_async_dns_lookup($host, function ($host, $ip){
                \Server\Coroutine\Coroutine::startCoroutine(function ($host, $ip){
                    if ($ip === '172.17.0.2'){ // 未知bug（可能和docker有关系），将正常ip定向到172.17.0.2
                        return;
                    }
                    
                    $redis = $this->getRedis()->getCoroutine();
                    yield $redis->hSet(
                        $this->getRedisHashName(), $host, \Swoole\Serialize::pack(['ip' => $ip?:false, 'time' => time() + 84000])
                    );
                    yield $this->getRedisMutex()->unlock($host); // 解锁
                }, [$host, $ip]);
            });
        }
    }
    

    
    
    protected function getRedisMutex()
    {
        if (is_null($this->redisMutex)) {
            $this->redisMutex = new \app\Helper\RedisMutex(get_instance()->redis_pool->getCoroutine());
        }
        
        return $this->redisMutex;
    }
    
    
}