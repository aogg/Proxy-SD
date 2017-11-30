<?php

namespace app;


class SDServer extends \app\AppServer
{
    
    /**
     * @param \Swoole\Server $service
     * @param $fd
     * @param $type
     * @param $data
     */
    public static function dispatchFunc($service, $fd, $type, $data)
    {
        $max = get_instance()->config->get('worker_num', 1);
        
        var_dump([get_class($service), $fd, $type]);
    }
    
    
    public function getEventControllerName()
    {
        return 'ProxyController';
    }
    
    public function onSwooleConnect($serv, $fd)
    {
        parent::onSwooleConnect($serv, $fd);
    
//        var_dump(__METHOD__);
        
    }
    
    
    public function onSwooleReceive($serv, $fd, $from_id, $data, $server_port = null)
    {
//        var_dump(__METHOD__);
        return parent::onSwooleReceive($serv, $fd, $from_id, $data, $server_port);
    }
    
    
}