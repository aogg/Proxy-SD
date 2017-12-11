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
    
    
    public static function setDns($set = null)
    {
        if (is_null($set)) {
            $set = get_instance()->config->get('server.dns') ?: 2;
        }
        
        if ($set === 1){ // 关闭DNS缓存
            \swoole_async_set([
                'disable_dns_cache' => true,
            ]);
        }else if($set === 2){ // DNS随机
            \swoole_async_set([
                'dns_lookup_random' => true,
            ]);
        }else if ($set === 0){ // 不设置
        
        }else{
            swoole_async_set(array(
                'dns_server' => $set,
            ));
        }
    }
    
    

    
    
    
    protected function getHostUseTry($host)
    {
        $ipBool = false;
        $ip = $host; // 默认ip返回host
        if (empty($host)){
            goto gotoReturn;
        }
        
        $hostMap = $this->getConfigHostMap($host);
        if (!is_null($hostMap)) {
            $ip = $hostMap;
            goto gotoReturn;
        }
        
        $data = yield $this->getHostUseMutex($host);
        
    
        // 正式处理缓存数据
        if (!is_null($data)) {
            $data = $this->unpackRedisData($data);
            
            // 过期，刷新等待
            if (isset($data['time']) && $this->ifRedisHostTimeExpire($data['time'])){
                $data = yield $this->getHostUseMutex($host, true);
                $data = $this->unpackRedisData($data);
            }else{ // 不等待，但更新
                $this->asyncDnsLookUp($host);
            }
            
            if (isset($data['ip']) && $data['ip'] === false){ // ip设为false时，返回false
                $ip = false;
            }else if(isset($data['ip'])){ // 正常处理
                $ip = $this->getConfigIpMap($data['ip']);
                if ($ip !== false){
                    $ipBool = true;
                }
            }
        }
    
        
        gotoReturn:;
        
        return [
            'ip' => $ip,
            'ipBool' => $ipBool,
        ];
    }
    
    
    protected function unpackRedisData($data)
    {
        $result = [];
        try{
            if (!empty($data)) {
                $result = \Swoole\Serialize::unpack($data);
            }
        }catch (\Exception $exception){ // 无法捕获
        
        }
        
        return $result;
    }
    
    
    protected function getHostUseMutex($host, $fresh = false)
    {
        $data = !$fresh ? yield $this->getRedis()->getCoroutine()->hGet($this->getRedisHashName(), $host) : null;
        
        if (is_null($data)){ // 第一次也要等swoole_async_dns_lookup返回
            if (is_null($data) && yield $this->getRedisMutex()->set($host)){// 给swoole_async_dns_lookup加锁
                $this->asyncDnsLookUp($host);
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
        
        return $data;
    }
    
    
    protected function getConfigIpMap($ip)
    {
        $data = get_instance()->config->get('proxy.ipMap');
    
        return isset($data[$ip]) ? $data[$ip] : $ip;
    }
    
    
    protected function getConfigHostMap($host)
    {
    
        $data = get_instance()->config->get('proxy.hostMap');
    
        return isset($data[$host]) ? $data[$host] : null;
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
    
    
    protected function ifRedisHostTimeExpire($time)
    {
        return empty($time) || $time < time();
    }
    
    
    protected function asyncDnsLookUp($host)
    {
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
    

    
    
    protected function getRedisMutex()
    {
        if (is_null($this->redisMutex)) {
            $this->redisMutex = new \app\Helper\RedisMutex(get_instance()->redis_pool->getCoroutine());
        }
        
        return $this->redisMutex;
    }
    
    
}